<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function out(array $data, int $status = 200): never {
    http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit;
}
function db(): PDO {
    $configFile = __DIR__ . '/db-config.php';
    if (!is_file($configFile)) out(['error'=>'Banco não configurado. Copie db-config.example.php para db-config.php e informe os dados MySQL.'], 503);
    $c = require $configFile;
    try { return new PDO("mysql:host={$c['host']};dbname={$c['database']};charset=utf8mb4", $c['user'], $c['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }
    catch (Throwable $e) { out(['error'=>'Não foi possível conectar ao banco de dados.'], 503); }
}
function body(): array { $v=json_decode(file_get_contents('php://input') ?: '', true); return is_array($v) ? $v : $_POST; }
function admin(): bool { return !empty($_SESSION['raffle_admin']); }
function requireAdmin(): void { if (!admin()) out(['error'=>'Acesso restrito aos colaboradores.'], 401); }
function clientIp(): string { return $_SERVER['REMOTE_ADDR'] ?? 'unknown'; }

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$pdo = db();
if ($action === 'register') {
    $closed = $pdo->query("SELECT valor FROM configuracoes WHERE chave='inscricoes_fechadas'")->fetchColumn();
    if ($closed === '1') out(['error'=>'As inscrições para este sorteio foram encerradas.'], 410);
    if (!admin()) {
        $bucket=(int)floor(time()/60); $ipHash=hash('sha256', clientIp());
        $limit=$pdo->prepare('INSERT INTO rate_limits (ip_hash, minute_bucket, requests) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE requests=IF(minute_bucket=VALUES(minute_bucket), requests+1, 1), minute_bucket=VALUES(minute_bucket)');
        $limit->execute([$ipHash,$bucket]);
        $count=$pdo->prepare('SELECT requests FROM rate_limits WHERE ip_hash=?'); $count->execute([$ipHash]);
        if ((int)$count->fetchColumn() > 5) out(['error'=>'Limite atingido: aguarde o próximo minuto para tentar novamente.'],429);
    }
    $data=body(); $number=filter_var($data['numero'] ?? null,FILTER_VALIDATE_INT);
    if (!$number || $number < 1 || $number > 3000) out(['error'=>'Informe um número de peito entre 01 e 3000.'],422);
    try { $q=$pdo->prepare('INSERT INTO sorteio (numero, sorteado) VALUES (?, 0)'); $q->execute([$number]); }
    catch (PDOException $e) { if ($e->getCode()==='23000') out(['error'=>'Este número de peito já está inscrito.'],409); throw $e; }
    out(['ok'=>true,'message'=>'Inscrição confirmada para o número '.str_pad((string)$number,2,'0',STR_PAD_LEFT).'.']);
}
if ($action === 'login') {
    $data=body(); $q=$pdo->prepare('SELECT id, senha FROM admins WHERE nome=? LIMIT 1'); $q->execute([trim((string)($data['nome'] ?? ''))]); $row=$q->fetch();
    if (!$row || !password_verify((string)($data['senha'] ?? ''),$row['senha'])) out(['error'=>'Credenciais inválidas.'],401);
    session_regenerate_id(true); $_SESSION['raffle_admin']=(int)$row['id']; out(['ok'=>true]);
}
if ($action === 'logout') { unset($_SESSION['raffle_admin']); out(['ok'=>true]); }
if ($action === 'status') { out(['closed'=>(bool)($pdo->query("SELECT valor FROM configuracoes WHERE chave='inscricoes_fechadas'")->fetchColumn() ?: 0)]); }
if ($action === 'close') { requireAdmin(); $v=body(); $q=$pdo->prepare("INSERT INTO configuracoes(chave,valor) VALUES('inscricoes_fechadas',?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)"); $q->execute([!empty($v['closed'])?'1':'0']); out(['ok'=>true]); }
if ($action === 'list') { requireAdmin(); $rows=$pdo->query('SELECT id, numero, sorteado FROM sorteio ORDER BY numero')->fetchAll(); out(['entries'=>$rows]); }
if ($action === 'draw') {
    requireAdmin();
    $pdo->beginTransaction();
    $q=$pdo->query('SELECT id,numero FROM sorteio WHERE sorteado=0 ORDER BY RAND() LIMIT 1 FOR UPDATE'); $winner=$q->fetch();
    if (!$winner) { $pdo->rollBack(); out(['error'=>'Não há participantes disponíveis.'],409); }
    $u=$pdo->prepare('UPDATE sorteio SET sorteado=1 WHERE id=?'); $u->execute([$winner['id']]); $pdo->commit(); out(['winner'=>(int)$winner['numero']]);
}
if ($action === 'load') { requireAdmin(); out(['entries'=>$pdo->query('SELECT id,numero,sorteado FROM sorteio ORDER BY numero')->fetchAll()]); }
out(['error'=>'Ação inválida.'],400);

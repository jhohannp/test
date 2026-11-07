<?php
/* Brazilian Drinks - envio do formulário de contato (PHP puro, sem serviços externos)
   CONFIGURE AQUI: */
$DESTINO   = 'comercial@braziliandrinks.com.br';   // quem recebe as mensagens
$REMETENTE = 'site@braziliandrinks.com.br';        // use um e-mail do SEU domínio (ex.: criado no painel da Locaweb)
$ASSUNTO   = 'Contato pelo site';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function fim($ok, $code = 200) { http_response_code($code); echo json_encode(['ok' => $ok]); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fim(false, 405);

// aceita JSON (enviado pelo site) ou formulário comum
$dados = json_decode(file_get_contents('php://input'), true);
if (!is_array($dados)) $dados = $_POST;

$limpa = function ($v, $max) { $v = trim((string)($v ?? '')); $v = str_replace(["\r", "\0"], '', $v); return mb_substr($v, 0, $max); };
$nome     = $limpa($dados['nome'] ?? '', 120);
$email    = $limpa($dados['email'] ?? '', 160);
$empresa  = $limpa($dados['empresa'] ?? '', 160);
$mensagem = $limpa($dados['mensagem'] ?? '', 4000);

if (!empty($dados['site'])) fim(true);                                   // isca anti-robô: finge sucesso
if ($nome === '' || $mensagem === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) fim(false, 422);
if (preg_match('/[\r\n]/', $nome . $email)) fim(false, 422);             // bloqueia injeção de cabeçalho

// limite simples: 1 envio a cada 30 segundos por visitante
$trava = sys_get_temp_dir() . '/bd_form_' . md5($_SERVER['REMOTE_ADDR'] ?? 'x');
if (file_exists($trava) && time() - filemtime($trava) < 30) fim(false, 429);
touch($trava);

$corpo = "Nome: $nome\nE-mail: $email\nEmpresa: " . ($empresa ?: '-') . "\n\nMensagem:\n$mensagem\n";
$cab = "From: Site Brazilian Drinks <$REMETENTE>\r\n"
     . "Reply-To: $nome <$email>\r\n"
     . "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
$assunto = '=?UTF-8?B?' . base64_encode("$ASSUNTO: $nome") . '?=';

fim(@mail($DESTINO, $assunto, $corpo, $cab, '-f' . $REMETENTE));

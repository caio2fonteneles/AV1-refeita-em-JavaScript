
<?php

header("Content-Type: application/json; charset=UTF-8");

// Caminhos dos arquivos
$arquivoPerguntas = __DIR__ . "/../dados/perguntas.txt";
$arquivoUsuarios = __DIR__ . "/../dados/usuarios.txt";

// Cria a pasta de dados caso não exista
if (!is_dir(__DIR__ . "/../dados")) {
    mkdir(__DIR__ . "/../dados", 0777, true);
}

// Recebe os dados enviados pelo JavaScript
$dados = json_decode(file_get_contents("php://input"), true);

// Função para devolver JSON
function responder($sucesso, $mensagem, $dados = null, $status = 200) {

    http_response_code($status);

    echo json_encode([
        "sucesso" => $sucesso,
        "mensagem" => $mensagem,
        "dados" => $dados
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Lê os registros do arquivo TXT
function lerArquivo($arquivo) {

    if (!file_exists($arquivo)) {
        return [];
    }

    $linhas = file($arquivo, FILE_IGNORE_NEW_LINES);

    $registros = [];

    foreach ($linhas as $linha) {

        if ($linha != "") {

            $registro = json_decode($linha, true);

            if (is_array($registro)) {
                $registros[] = $registro;
            }
        }
    }

    return $registros;
}

// Salva todos os registros no arquivo TXT
function salvarArquivo($arquivo, $registros) {

    $conteudo = "";

    foreach ($registros as $registro) {

        $conteudo .= json_encode(
            $registro,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) . PHP_EOL;
    }

    return file_put_contents($arquivo, $conteudo, LOCK_EX) !== false;
}

// Procura um registro pelo ID
function buscarPorId($registros, $id) {

    foreach ($registros as $registro) {

        if ($registro["id"] == $id) {
            return $registro;
        }
    }

    return null;
}

// Verifica se a requisição foi enviada corretamente
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    responder(false, "Método não permitido.", null, 405);
}

if (!is_array($dados) || empty($dados["acao"])) {
    responder(false, "Requisição inválida.", null, 400);
}

// Identifica a operação solicitada
$acao = $dados["acao"];

switch ($acao) {

    // ==================================
    // CRIAR PERGUNTA
    // ==================================

    case "criar_pergunta":

        $desafio = trim($dados["desafio"] ?? "");
        $pergunta = trim($dados["pergunta"] ?? "");
        $tipo = $dados["tipo"] ?? "";

        if ($desafio == "" || $pergunta == "") {
            responder(false, "Preencha todos os campos.");
        }

        $registro = [
            "id" => uniqid(),
            "desafio" => $desafio,
            "pergunta" => $pergunta,
            "tipo" => $tipo
        ];

        if ($tipo == "multipla") {

            $opcoes = $dados["opcoes"] ?? [];
            $correta = $dados["correta"] ?? "";

            if (!is_array($opcoes) || count($opcoes) != 4) {
                responder(false, "Informe as quatro opções.");
            }

            foreach ($opcoes as $opcao) {

                if (trim($opcao) == "") {
                    responder(false, "Preencha todas as opções.");
                }
            }

            if (!in_array((string)$correta, ["1", "2", "3", "4"], true)) {
                responder(false, "Selecione a resposta correta.");
            }

            $registro["opcoes"] = $opcoes;
            $registro["correta"] = (string)$correta;

        } elseif ($tipo == "texto") {

            $resposta = trim($dados["resposta"] ?? "");

            if ($resposta == "") {
                responder(false, "Informe a resposta esperada.");
            }

            $registro["resposta"] = $resposta;

        } else {

            responder(false, "Tipo de pergunta inválido.");
        }

        $registros = lerArquivo($arquivoPerguntas);

        $registros[] = $registro;

        if (!salvarArquivo($arquivoPerguntas, $registros)) {
            responder(false, "Erro ao salvar pergunta.", null, 500);
        }

        responder(true, "Pergunta cadastrada com sucesso!");


    // ==================================
    // LISTAR PERGUNTAS
    // ==================================

    case "listar_perguntas":

        $registros = lerArquivo($arquivoPerguntas);

        responder(true, "Perguntas encontradas.", $registros);


    // ==================================
    // VISUALIZAR UMA PERGUNTA
    // ==================================

    case "obter_pergunta":

        $id = $dados["id"] ?? "";

        $registros = lerArquivo($arquivoPerguntas);

        $registro = buscarPorId($registros, $id);

        if ($registro == null) {
            responder(false, "Pergunta não encontrada.", null, 404);
        }

        responder(true, "Pergunta encontrada.", $registro);


    // ==================================
    // ALTERAR PERGUNTA
    // ==================================

    case "editar_pergunta":

        $id = $dados["id"] ?? "";

        $registros = lerArquivo($arquivoPerguntas);

        $encontrado = false;

        foreach ($registros as $i => $registro) {

            if ($registro["id"] == $id) {

                $tipo = $dados["tipo"] ?? "";

                $desafio = trim($dados["desafio"] ?? "");
                $pergunta = trim($dados["pergunta"] ?? "");

                if ($desafio == "" || $pergunta == "") {
                    responder(false, "Preencha todos os campos.");
                }

                if ($tipo == "multipla") {

                    $opcoes = $dados["opcoes"] ?? [];
                    $correta = $dados["correta"] ?? "";

                    if (!is_array($opcoes) || count($opcoes) != 4) {
                        responder(false, "Informe as quatro opções.");
                    }

                    foreach ($opcoes as $opcao) {

                        if (trim($opcao) == "") {
                            responder(false, "Preencha todas as opções.");
                        }
                    }

                    if (!in_array((string)$correta, ["1", "2", "3", "4"], true)) {
                        responder(false, "Selecione a resposta correta.");
                    }

                    $registros[$i] = [
                        "id" => $id,
                        "desafio" => $desafio,
                        "pergunta" => $pergunta,
                        "tipo" => $tipo,
                        "opcoes" => $opcoes,
                        "correta" => (string)$correta
                    ];

                } elseif ($tipo == "texto") {

                    $resposta = trim($dados["resposta"] ?? "");

                    if ($resposta == "") {
                        responder(false, "Informe a resposta esperada.");
                    }

                    $registros[$i] = [
                        "id" => $id,
                        "desafio" => $desafio,
                        "pergunta" => $pergunta,
                        "tipo" => $tipo,
                        "resposta" => $resposta
                    ];

                } else {

                    responder(false, "Tipo inválido.");
                }

                $encontrado = true;

                break;
            }
        }

        if (!$encontrado) {
            responder(false, "Pergunta não encontrada.", null, 404);
        }

        if (!salvarArquivo($arquivoPerguntas, $registros)) {
            responder(false, "Erro ao alterar pergunta.", null, 500);
        }

        responder(true, "Pergunta alterada com sucesso!");


    // ==================================
    // EXCLUIR PERGUNTA
    // ==================================

    case "excluir_pergunta":

        $id = $dados["id"] ?? "";

        $registros = lerArquivo($arquivoPerguntas);

        $novosRegistros = [];

        $encontrado = false;

        foreach ($registros as $registro) {

            if ($registro["id"] == $id) {

                $encontrado = true;

            } else {

                $novosRegistros[] = $registro;
            }
        }

        if (!$encontrado) {
            responder(false, "Pergunta não encontrada.", null, 404);
        }

        if (!salvarArquivo($arquivoPerguntas, $novosRegistros)) {
            responder(false, "Erro ao excluir pergunta.", null, 500);
        }

        responder(true, "Pergunta excluída com sucesso!");


    // ==================================
    // CRIAR USUÁRIO
    // ==================================

    case "criar_usuario":

        $nome = trim($dados["nome"] ?? "");
        $email = trim($dados["email"] ?? "");

        if ($nome == "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            responder(false, "Informe um nome e um e-mail válido.");
        }

        $registro = [
            "id" => uniqid(),
            "nome" => $nome,
            "email" => $email
        ];

        $registros = lerArquivo($arquivoUsuarios);

        $registros[] = $registro;

        if (!salvarArquivo($arquivoUsuarios, $registros)) {
            responder(false, "Erro ao cadastrar usuário.", null, 500);
        }

        responder(true, "Usuário cadastrado com sucesso!");


    // ==================================
    // LISTAR USUÁRIOS
    // ==================================

    case "listar_usuarios":

        $registros = lerArquivo($arquivoUsuarios);

        responder(true, "Usuários encontrados.", $registros);


    // ==================================
    // OBTER UM USUÁRIO
    // ==================================

    case "obter_usuario":

        $id = $dados["id"] ?? "";

        $registros = lerArquivo($arquivoUsuarios);

        $registro = buscarPorId($registros, $id);

        if ($registro == null) {
            responder(false, "Usuário não encontrado.", null, 404);
        }

        responder(true, "Usuário encontrado.", $registro);


    // ==================================
    // ALTERAR USUÁRIO
    // ==================================

    case "editar_usuario":

        $id = $dados["id"] ?? "";

        $nome = trim($dados["nome"] ?? "");
        $email = trim($dados["email"] ?? "");

        if ($nome == "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            responder(false, "Informe um nome e um e-mail válido.");
        }

        $registros = lerArquivo($arquivoUsuarios);

        $encontrado = false;

        foreach ($registros as $i => $registro) {

            if ($registro["id"] == $id) {

                $registros[$i]["nome"] = $nome;
                $registros[$i]["email"] = $email;

                $encontrado = true;

                break;
            }
        }

        if (!$encontrado) {
            responder(false, "Usuário não encontrado.", null, 404);
        }

        if (!salvarArquivo($arquivoUsuarios, $registros)) {
            responder(false, "Erro ao alterar usuário.", null, 500);
        }

        responder(true, "Usuário alterado com sucesso!");


    // ==================================
    // EXCLUIR USUÁRIO
    // ==================================

    case "excluir_usuario":

        $id = $dados["id"] ?? "";

        $registros = lerArquivo($arquivoUsuarios);

        $novosRegistros = [];

        $encontrado = false;

        foreach ($registros as $registro) {

            if ($registro["id"] == $id) {

                $encontrado = true;

            } else {

                $novosRegistros[] = $registro;
            }
        }

        if (!$encontrado) {
            responder(false, "Usuário não encontrado.", null, 404);
        }

        if (!salvarArquivo($arquivoUsuarios, $novosRegistros)) {
            responder(false, "Erro ao excluir usuário.", null, 500);
        }

        responder(true, "Usuário excluído com sucesso!");


    // ==================================
    // AÇÃO NÃO RECONHECIDA
    // ==================================

    default:

        responder(false, "Ação não reconhecida.", null, 400);
}

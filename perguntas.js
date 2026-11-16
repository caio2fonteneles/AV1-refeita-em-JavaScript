
const API = "php/api.php";

const form = document.getElementById("formPergunta");
const tipo = document.getElementById("tipo");

const camposMultipla = document.getElementById("camposMultipla");
const camposTexto = document.getElementById("camposTexto");

const lista = document.getElementById("listaPerguntas");
const detalhe = document.getElementById("detalhePergunta");

let perguntas = [];

// Envia uma requisição para o PHP
async function requisicao(dados) {

    const resposta = await fetch(API, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(dados)
    });

    const resultado = await resposta.json();

    if (!resposta.ok || !resultado.sucesso) {
        throw new Error(resultado.mensagem || "Erro na operação.");
    }

    return resultado;
}

// Mostra os campos de acordo com o tipo
function mostrarCampos() {

    const multipla = tipo.value === "multipla";

    camposMultipla.hidden = !multipla;
    camposTexto.hidden = multipla;

    ["opcao1", "opcao2", "opcao3", "opcao4"].forEach(id => {
        document.getElementById(id).required = multipla;
    });

    document.getElementById("resposta").required = !multipla;
}

tipo.addEventListener("change", mostrarCampos);

mostrarCampos();

// Cadastra ou altera uma pergunta
form.addEventListener("submit", async function(event) {

    event.preventDefault();

    const id = document.getElementById("id").value;

    const dados = {
        acao: id ? "editar_pergunta" : "criar_pergunta",

        id: id,
        desafio: document.getElementById("desafio").value,
        pergunta: document.getElementById("pergunta").value,
        tipo: tipo.value
    };

    if (tipo.value === "multipla") {

        dados.opcoes = [
            document.getElementById("opcao1").value,
            document.getElementById("opcao2").value,
            document.getElementById("opcao3").value,
            document.getElementById("opcao4").value
        ];

        dados.correta = document.getElementById("correta").value;

    } else {

        dados.resposta = document.getElementById("resposta").value;
    }

    try {

        const resultado = await requisicao(dados);

        alert(resultado.mensagem);

        form.reset();

        document.getElementById("id").value = "";

        document.getElementById("tituloFormulario").textContent =
            "Criar Pergunta";

        document.getElementById("cancelarEdicao").hidden = true;

        mostrarCampos();

        carregarPerguntas();

    } catch (erro) {

        alert(erro.message);
    }

});

// Lista todas as perguntas
async function carregarPerguntas() {

    try {

        const resultado = await requisicao({
            acao: "listar_perguntas"
        });

        perguntas = resultado.dados;

        lista.innerHTML = "";

        if (perguntas.length === 0) {

            lista.textContent = "Nenhuma pergunta cadastrada.";

            return;
        }

        perguntas.forEach(p => {

            const div = document.createElement("div");

            const titulo = document.createElement("h3");
            titulo.textContent = p.desafio;

            const texto = document.createElement("p");
            texto.textContent = p.pergunta;

            const visualizar = document.createElement("button");
            visualizar.textContent = "Visualizar";

            visualizar.onclick = () => visualizarPergunta(p.id);

            const editar = document.createElement("button");
            editar.textContent = "Editar";

            editar.onclick = () => editarPergunta(p.id);

            const excluir = document.createElement("button");
            excluir.textContent = "Excluir";

            excluir.onclick = () => excluirPergunta(p.id);

            div.append(titulo, texto, visualizar, editar, excluir);

            lista.appendChild(div);

            const linha = document.createElement("hr");
            lista.appendChild(linha);

        });

    } catch (erro) {

        alert(erro.message);
    }
}

// Visualiza uma pergunta específica
async function visualizarPergunta(id) {

    try {

        const resultado = await requisicao({
            acao: "obter_pergunta",
            id: id
        });

        const p = resultado.dados;

        detalhe.innerHTML = "";

        const titulo = document.createElement("h2");
        titulo.textContent = "Detalhes da pergunta";

        const texto = document.createElement("p");
        texto.textContent = p.pergunta;

        detalhe.append(titulo, texto);

        if (p.tipo === "multipla") {

            p.opcoes.forEach((opcao, i) => {

                const item = document.createElement("p");

                item.textContent =
                    "Opção " + (i + 1) + ": " + opcao;

                detalhe.appendChild(item);
            });

            const correta = document.createElement("p");

            correta.textContent =
                "Resposta correta: Opção " + p.correta;

            detalhe.appendChild(correta);

        } else {

            const resposta = document.createElement("p");

            resposta.textContent = "Resposta esperada: " + p.resposta;

            detalhe.appendChild(resposta);
        }

    } catch (erro) {

        alert(erro.message);
    }
}

// Carrega os dados no formulário para edição
async function editarPergunta(id) {

    try {

        const resultado = await requisicao({
            acao: "obter_pergunta",
            id: id
        });

        const p = resultado.dados;

        document.getElementById("id").value = p.id;
        document.getElementById("desafio").value = p.desafio;
        document.getElementById("pergunta").value = p.pergunta;

        tipo.value = p.tipo;

        mostrarCampos();

        if (p.tipo === "multipla") {

            p.opcoes.forEach((opcao, i) => {

                document.getElementById("opcao" + (i + 1)).value = opcao;
            });

            document.getElementById("correta").value = p.correta;

        } else {

            document.getElementById("resposta").value = p.resposta;
        }

        document.getElementById("tituloFormulario").textContent =
            "Editar Pergunta";

        document.getElementById("cancelarEdicao").hidden = false;

        window.scrollTo(0, 0);

    } catch (erro) {

        alert(erro.message);
    }
}

// Exclui uma pergunta
async function excluirPergunta(id) {

    if (!confirm("Deseja realmente excluir esta pergunta?")) {
        return;
    }

    try {

        const resultado = await requisicao({
            acao: "excluir_pergunta",
            id: id
        });

        alert(resultado.mensagem);

        detalhe.innerHTML = "";

        carregarPerguntas();

    } catch (erro) {

        alert(erro.message);
    }
}

// Cancela uma edição
document.getElementById("cancelarEdicao").onclick = function() {

    form.reset();

    document.getElementById("id").value = "";

    document.getElementById("tituloFormulario").textContent =
        "Criar Pergunta";

    this.hidden = true;

    mostrarCampos();
};

// Inicia a listagem
carregarPerguntas();

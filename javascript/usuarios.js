
const API = "php/api.php";

const formUsuario = document.getElementById("formUsuario");
const listaUsuarios = document.getElementById("listaUsuarios");

// Envia os dados para o PHP
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

// Cadastra ou altera usuário
formUsuario.addEventListener("submit", async function(event) {

    event.preventDefault();

    const id = document.getElementById("id").value;

    const dados = {

        acao: id ? "editar_usuario" : "criar_usuario",

        id: id,

        nome: document.getElementById("nome").value,

        email: document.getElementById("email").value
    };

    try {

        const resultado = await requisicao(dados);

        alert(resultado.mensagem);

        formUsuario.reset();

        document.getElementById("id").value = "";

        document.getElementById("tituloUsuario").textContent =
            "Cadastrar Usuário";

        document.getElementById("cancelar").hidden = true;

        carregarUsuarios();

    } catch (erro) {

        alert(erro.message);
    }

});

// Lista usuários
async function carregarUsuarios() {

    try {

        const resultado = await requisicao({
            acao: "listar_usuarios"
        });

        listaUsuarios.innerHTML = "";

        if (resultado.dados.length === 0) {

            listaUsuarios.textContent = "Nenhum usuário cadastrado.";

            return;
        }

        resultado.dados.forEach(usuario => {

            const div = document.createElement("div");

            const nome = document.createElement("p");
            nome.textContent = "Nome: " + usuario.nome;

            const email = document.createElement("p");
            email.textContent = "E-mail: " + usuario.email;

            const editar = document.createElement("button");
            editar.textContent = "Editar";

            editar.onclick = () => editarUsuario(usuario.id);

            const excluir = document.createElement("button");
            excluir.textContent = "Excluir";

            excluir.onclick = () => excluirUsuario(usuario.id);

            div.append(nome, email, editar, excluir);

            listaUsuarios.appendChild(div);

            listaUsuarios.appendChild(document.createElement("hr"));
        });

    } catch (erro) {

        alert(erro.message);
    }
}

// Busca usuário para edição
async function editarUsuario(id) {

    try {

        const resultado = await requisicao({
            acao: "obter_usuario",
            id: id
        });

        const usuario = resultado.dados;

        document.getElementById("id").value = usuario.id;
        document.getElementById("nome").value = usuario.nome;
        document.getElementById("email").value = usuario.email;

        document.getElementById("tituloUsuario").textContent =
            "Editar Usuário";

        document.getElementById("cancelar").hidden = false;

        window.scrollTo(0, 0);

    } catch (erro) {

        alert(erro.message);
    }
}

// Exclui usuário
async function excluirUsuario(id) {

    if (!confirm("Deseja excluir este usuário?")) {
        return;
    }

    try {

        const resultado = await requisicao({
            acao: "excluir_usuario",
            id: id
        });

        alert(resultado.mensagem);

        carregarUsuarios();

    } catch (erro) {

        alert(erro.message);
    }
}

// Cancela edição
document.getElementById("cancelar").onclick = function() {

    formUsuario.reset();

    document.getElementById("id").value = "";

    document.getElementById("tituloUsuario").textContent =
        "Cadastrar Usuário";

    this.hidden = true;
};

// Inicia a listagem
carregarUsuarios();

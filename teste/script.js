// script.js
function abrirModal(numero, arcada) {
    const modal = document.getElementById('modalTratamento');
    modal.classList.add('show');

    const modalTitle = document.getElementById('modal-title');
    const inputDente = document.getElementById('inputDente');
    const inputArcada = document.getElementById('inputArcada');

    if (arcada === 'Geral') {
        modalTitle.innerText = 'Tratamento geral';
    } else {
        modalTitle.innerText = arcada + ' - Dente ' + numero;
    }
    
    inputDente.value = numero;
    inputArcada.value = arcada;
}

function fecharModal() {
    const modal = document.getElementById('modalTratamento');
    modal.classList.remove('show');
}

// Fechar modal ao clicar fora dele
window.onclick = function(event) {
    const modal = document.getElementById('modalTratamento');
    if (event.target == modal) {
        fecharModal();
    }
}

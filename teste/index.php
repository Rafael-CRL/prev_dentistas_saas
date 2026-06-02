<!DOCTYPE html>
<html lang="pt-br">
    <!-- Imagens coletadas do site
     https://www.image-map.net/
     Serve para mapeamento de coordenadas em imagem.
    -->
<head>
    <meta charset="UTF-8">
    <title>Odontograma - Prev Dentista</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="card">
    <h2>Odontograma do Paciente</h2>
    <p>Selecione um dente para registrar o tratamento:</p>
    

    <div class="canvas-container">
        <img src="../assets/img/odontograma.png" usemap="#image-map" class="img-odontograma">
        <map name="image-map">
            <!-- Arcada Superior -->
            <area target="" onclick="abrirModal(18, 'Arcada Superior')" alt="Molar 18" id="d18" title="3º Molar" coords="53,251,99,157" shape="rect">
            <area target="" onclick="abrirModal(17, 'Arcada Superior')" alt="Molar 17" id="d17" title="2º Molar" coords="147,156,103,249" shape="rect">
            <area target="" onclick="abrirModal(16, 'Arcada Superior')" alt="Molar 16" title="Molar 16" coords="207,155,151,246" shape="rect">
            <area target="" onclick="abrirModal(15, 'Arcada Superior')" alt="Premolar 15" title="Premolar 15" coords="241,152,209,242" shape="rect">
            <area target="" onclick="abrirModal(14, 'Arcada Superior')" alt="Premolar 14" title="Premolar 14" coords="274,149,246,241" shape="rect">
            <area target="" onclick="abrirModal(13, 'Arcada Superior')" alt="Canino 13" title="Canino 13" coords="314,148,277,238" shape="rect">
            <area target="" onclick="abrirModal(12, 'Arcada Superior')" alt="Inciso 12" title="Inciso 12" coords="352,152,317,243" shape="rect">
            <area target="" onclick="abrirModal(11, 'Arcada Superior')" alt="Inciso 11" title="Inciso 11" coords="397,153,355,246" shape="rect">
            <area target="" onclick="abrirModal(21, 'Arcada Superior')" alt="Inciso 21" title="Inciso 21" coords="442,154,403,244" shape="rect">
            <area target="" onclick="abrirModal(22, 'Arcada Superior')" alt="Inciso 22" title="Inciso 22" coords="479,153,446,243" shape="rect">
            <area target="" onclick="abrirModal(23, 'Arcada Superior')" alt="Canino 23" title="Canino 23" coords="521,142,481,243" shape="rect">
            <area target="" onclick="abrirModal(24, 'Arcada Superior')" alt="Premolar 24" title="Premolar 24" coords="561,146,525,239" shape="rect">
            <area target="" onclick="abrirModal(25, 'Arcada Superior')" alt="Premolar 25" title="Premolar 25" coords="590,146,564,237" shape="rect">
            <area target="" onclick="abrirModal(26, 'Arcada Superior')" alt="Molar 26" title="Molar 26" coords="648,148,593,238" shape="rect">
            <area target="" onclick="abrirModal(27, 'Arcada Superior')" alt="Molar 27" title="Molar 27" coords="703,151,653,239" shape="rect">
            <area target="" onclick="abrirModal(28, 'Arcada Superior')" alt="Molar 28" id="d28" title="3º Molar" coords="741,149,705,241" shape="rect">
            
            <!-- Arcada Inferior -->
            <area target="" onclick="abrirModal(48, 'Arcada Inferior')" alt="Molar 48" id="d48" title="Molar 48" coords="51,285,103,360" shape="rect">
            <area target="" onclick="abrirModal(47, 'Arcada Inferior')" alt="Molar 47" title="Molar 47" coords="109,284,160,363" shape="rect">
            <area target="" onclick="abrirModal(46, 'Arcada Inferior')" alt="Molar 46" title="Molar 46" coords="167,281,219,363" shape="rect">
            <area target="" onclick="abrirModal(45, 'Arcada Inferior')" alt="Premolar 45" title="Premolar 45" coords="221,278,258,378" shape="rect">
            <area target="" onclick="abrirModal(44, 'Arcada Inferior')" alt="Premolar 44" title="Premolar 44" coords="260,275,296,390" shape="rect">
            <area target="" onclick="abrirModal(43, 'Arcada Inferior')" alt="Canino 43" title="Canino 43" coords="298,276,336,384" shape="rect">
            <area target="" onclick="abrirModal(42, 'Arcada Inferior')" alt="Inciso 42" title="Inciso 42" coords="338,275,368,384" shape="rect">
            <area target="" onclick="abrirModal(41, 'Arcada Inferior')" alt="Inciso 41" title="Inciso 41" coords="370,276,395,383" shape="rect">
            <area target="" onclick="abrirModal(31, 'Arcada Inferior')" alt="Inciso 31" title="Inciso 31" coords="398,275,426,380" shape="rect">
            <area target="" onclick="abrirModal(32, 'Arcada Inferior')" alt="Inciso 32" title="Inciso 32" coords="428,275,454,382" shape="rect">
            <area target="" onclick="abrirModal(33, 'Arcada Inferior')" alt="Canino 33" title="Canino 33" coords="456,274,493,391" shape="rect">
            <area target="" onclick="abrirModal(34, 'Arcada Inferior')" alt="Premolar 34" title="Premolar 34" coords="496,274,531,383" shape="rect">
            <area target="" onclick="abrirModal(35, 'Arcada Inferior')" alt="Premolar 35" title="Premolar 35" coords="534,274,571,379" shape="rect">
            <area target="" onclick="abrirModal(36, 'Arcada Inferior')" alt="Molar 36" title="Molar 36" coords="575,274,636,384" shape="rect">
            <area target="" onclick="abrirModal(37, 'Arcada Inferior')" alt="Molar 37" title="Molar 37" coords="640,274,688,384" shape="rect">
            <area target="" onclick="abrirModal(38, 'Arcada Inferior')" alt="Molar 38" title="Molar 38" coords="694,272,742,375" shape="rect">

            <!-- Geral -->
            <area target="" onclick="abrirModal('Todos', 'Geral')" alt="Todos" title="Todos" coords="85,31,727,83" shape="rect">
            <area target="" onclick="abrirModal('Todos', 'Geral')" alt="Todos" title="Todos" coords="72,449,727,498" shape="rect">
        </map>
    </div>
</div>


<div id="modalTratamento" class="modal">
    <div class="modal-content">
        <h3><span id="modal-title"></span></h3>
        <form action="salvar_tratamento.php" method="POST">
            <input type="hidden" name="dente_id" id="inputDente">
            <input type="hidden" name="arcada" id="inputArcada">
            
            <label for="tratamento">Tratamento:</label>
            <select name="tratamento" required>
                <option value="limpeza">Limpeza</option>
                <option value="canal">Tratamento de Canal</option>
                <option value="extracao">Extração</option>
                <option value="restauracao">Restauração</option>
            </select>

            <div class="btn-group">
                <button type="submit" class="btn-save">Salvar</button>
                <button type="button" onclick="fecharModal()" class="btn-cancel">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>
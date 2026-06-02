<?php
// salvar_tratamento.php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dente = $_POST['dente_id'];
    $tratamento = $_POST['tratamento'];
    
    // Supondo que você já tenha a conexão $conn com o MySQL
    // $sql = "INSERT INTO tratamentos (dente_numero, descricao) VALUES ('$dente', '$tratamento')";
    
    echo "Sucesso: O dente $dente foi marcado para $tratamento.";
    // header("Location: odontograma.php"); // Redireciona de volta
}
?>
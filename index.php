<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Golampi Parser Playground</title>
    <link rel="stylesheet" href="/static/style.css">
</head>
<body>
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Antlr\Antlr4\Runtime\InputStream;
use Antlr\Antlr4\Runtime\CommonTokenStream;
use App\Generated\GolampiLexer;
use App\Generated\GolampiParser;
use App\Custom\Interpreter;

$input = "";
$output = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["expression"])) {
    $input = $_POST["expression"];

    try {
        // 1. Configuración de ANTLR
        $inputStream = InputStream::fromString($input);
        $lexer = new GolampiLexer($inputStream);
        $tokens = new CommonTokenStream($lexer);
        $parser = new GolampiParser($tokens);
        $tree = $parser->start();

        // 2. Ejecutar el Intérprete
        $interpreter = new Interpreter();

        ob_start();
        $interpreter->visit($tree);
        $output = ob_get_clean();

    }catch(\Throwable $e) {
        $output = "Error Fatal: " . $e->getMessage();
    }
}
?>

<h2>Entrada (Golampi)</h2>

<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
    <div class="editor-container">
        <div class="line-numbers" id="lineNumbers">1</div>
        <textarea id="editor" name="expression" placeholder="Escriba su código aquí..."><?php echo htmlspecialchars($input)?></textarea>
    </div>
    <input type="submit" value="Run" style="padding: 10px 20px; cursor: pointer;">
</form>

<h2>Salida:</h2>
<div class="console">
    <?php
    if (!empty($output)) {
        echo htmlspecialchars($output);
    } elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
        echo "El código se ejecutó, pero no hubo salida.";
    } else {
        echo "Por favor ingrese código para interpretar.";
    }
    ?>
</div>

<script src="/static/script.js"></script>
</body>
</html>
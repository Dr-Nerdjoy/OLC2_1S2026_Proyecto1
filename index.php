<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Golampi IDE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #1e1e1e; color: #fff; }
        .editor-container { height: 50vh; margin-bottom: 20px;}
        textarea {
            width: 100%; height: 100%; background-color: #2d2d2d;
            color: #d4d4d4; font-family: monospace; padding: 15px;
            border: 1px solid #444; border-radius: 5px; resize: none;
        }
        .consola {
            width: 100%; min-height: 150px; background-color: #000;
            color: #00ff00; font-family: monospace; padding: 15px;
            border: 1px solid #444; border-radius: 5px; white-space: pre-wrap;
        }
        .tabla-errores th { background-color: #dc3545; color: white; }
    </style>
</head>
<body>
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Antlr\Antlr4\Runtime\InputStream;
use Antlr\Antlr4\Runtime\CommonTokenStream;
use App\Generated\GolampiLexer;
use App\Generated\GolampiParser;
use App\Custom\Interpreter;
use App\Custom\ErrorListener;
use App\Custom\DotGenerator;
$input = "";
$output = "";
$listaErrores = [];
$dotString = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["expression"])) {
    $input = $_POST["expression"];

    try {
        $inputStream = InputStream::fromString($input);
        $lexer = new GolampiLexer($inputStream);

        // 1. Inyectar el Cazador de Errores
        $errorListener = new ErrorListener();
        $lexer->removeErrorListeners();
        $lexer->addErrorListener($errorListener);

        $tokens = new CommonTokenStream($lexer);
        $parser = new GolampiParser($tokens);

        $parser->removeErrorListeners();
        $parser->addErrorListener($errorListener);

        $tree = $parser->start();

        // 2. Extraer errores Léxicos y Sintácticos
        $listaErrores = $errorListener->errores;

        // 3. Ejecutar solo si no hay errores graves de sintaxis
        if (empty($listaErrores)) {
            $interpreter = new Interpreter();
            ob_start();
            $interpreter->visit($tree);
            $output = ob_get_clean();

            $dotGen = new DotGenerator();
            $dotString = $dotGen->generate($tree);
        } else {
            $output = "La ejecución se detuvo debido a errores léxicos/sintácticos.";
        }

    } catch(\Throwable $e) {
        // 4. Capturar errores Semánticos (Variables, tipos, etc.)
        $listaErrores[] = [
                "tipo" => "Semántico",
                "linea" => "-",
                "columna" => "-",
                "mensaje" => $e->getMessage()
        ];
        $output .= "\n\nError Fatal en tiempo de ejecución.";
    }
}
?>

<div class="container mt-4">
    <h2 class="text-center mb-4 text-warning">⚡ Golampi IDE</h2>

    <form method="post" action="">
        <div class="row editor-container">
            <div class="col-md-12">
                <textarea name="expression" placeholder="Escriba su código Golampi aquí..."><?php echo htmlspecialchars($input)?></textarea>
            </div>
        </div>
        <button type="submit" class="btn btn-success mb-4 fw-bold">▶ Compilar y Ejecutar</button>
    </form>

    <div class="row">
        <div class="col-md-12 mb-4">
            <h4>🖥️ Consola de Salida:</h4>
            <div class="consola"><?php echo htmlspecialchars($output); ?></div>
        </div>
    </div>

    <?php if (!empty($listaErrores)): ?>
        <div class="row">
            <div class="col-md-12 mt-4">
                <h4 class="text-danger">🚨 Reporte de Errores</h4>
                <table class="table table-dark table-striped tabla-errores">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Tipo</th>
                        <th>Línea</th>
                        <th>Columna</th>
                        <th>Descripción</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($listaErrores as $index => $error): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><span class="badge bg-danger"><?php echo $error['tipo']; ?></span></td>
                            <td><?php echo $error['linea']; ?></td>
                            <td><?php echo $error['columna']; ?></td>
                            <td><?php echo htmlspecialchars($error['mensaje']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>


<?php if (!empty($dotString)): ?>
    <div class="row">
        <div class="col-md-12 mt-4 mb-5">
            <h4 class="text-info">🌳 Árbol de Sintaxis Abstracta (AST)</h4>
            <div id="ast-graph" style="background-color: #1e1e1e; border: 1px solid #444; border-radius: 5px; text-align: center; overflow: hidden; padding: 20px;"></div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/viz.js/2.1.2/viz.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/viz.js/2.1.2/full.render.js"></script>

    <script>
        var dotCode = `<?php echo $dotString; ?>`;
        if (dotCode.trim() !== "") {
            var viz = new Viz();
            viz.renderSVGElement(dotCode)
                .then(function(element) {
                    // Limpiamos la caja y metemos el dibujo
                    var astContainer = document.getElementById("ast-graph");
                    astContainer.innerHTML = "";

                    // Ajustamos el tamaño para que se vea bien
                    element.style.width = "100%";
                    element.style.height = "auto";

                    astContainer.appendChild(element);
                })
                .catch(error => {
                    console.error("Error al dibujar el árbol:", error);
                });
        }
    </script>



<?php endif; ?>

</body>
</html>
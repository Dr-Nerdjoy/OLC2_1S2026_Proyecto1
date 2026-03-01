<?php

require __DIR__ . '/vendor/autoload.php';

use Antlr\Antlr4\Runtime\InputStream;
use Antlr\Antlr4\Runtime\CommonTokenStream;
use App\Generated\GolampiLexer;
use App\Generated\GolampiParser;
use App\Custom\Interpreter;

// 1. Entrada del usuario (Prueba rápida)
$input = '
    int i = 0;
    while (i < 10) {
        print(i);
        if (i == 5) {
            print("Llegamos al limite, haciendo break!");
            break;
        }
        i = i + 1;
    }
    print("Ciclo terminado.");
';

// 2. Configuración de ANTLR
$inputStream = InputStream::fromString($input);
$lexer = new GolampiLexer($inputStream);
$tokens = new CommonTokenStream($lexer);
$parser = new GolampiParser($tokens);

// 3. Construir el Árbol (Empezando desde la regla 'start')
$tree = $parser->start();

// 4. Ejecutar el Intérprete (Visitar el árbol)
$interpreter = new Interpreter();
$interpreter->visit($tree);
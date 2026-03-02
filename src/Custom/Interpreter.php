<?php

namespace App\Custom;
require_once __DIR__ . '/FlowTypes.php';
use App\Generated\GolampiBaseVisitor;

use App\Generated\GolampiParser;

class Interpreter extends GolampiBaseVisitor {

    private $environment;

    public function __construct() {
        // Inicializamos el entorno global
        $this->environment = new Environment(null);
    }

    // ==========================================
    // 1. SALIDA (Print)
    // ==========================================
    public function visitPrintStmt($ctx) {
        // "print" "(" expr ")" ";"
        // $ctx->expr() nos da el nodo de la expresión interna
        $valor = $this->visit($ctx->expr());

        // Imprimimos en la consola de PHP
        echo $valor . "\n";

        return null;
    }

    // ==========================================
    // 2. OPERACIONES ARITMÉTICAS
    // ==========================================
    public function visitAddSub($ctx) {
        // expr op=('+'|'-') expr
        $left = $this->visit($ctx->expr(0));  // El de la izquierda
        $right = $this->visit($ctx->expr(1)); // El de la derecha
        $op = $ctx->op->getText();            // El símbolo (+ o -)

        if ($op == '+') return $left + $right;
        if ($op == '-') return $left - $right;
        return 0;
    }

    public function visitMulDiv($ctx) {
        $left = $this->visit($ctx->expr(0));
        $right = $this->visit($ctx->expr(1));
        $op = $ctx->op->getText();

        if ($op == '*') return $left * $right;
        if ($op == '/') return $left / $right;
        if ($op == '%') return $left % $right;
        return 0;
    }


    // ==========================================
    // MANEJO DE VARIABLES
    // ==========================================

    // Declaración: int limite = 5;
    public function visitDeclaracionStmt($ctx) {
        $id = $ctx->ID()->getText();
        $valor = $this->visit($ctx->expr());

        $this->environment->guardar($id, $valor);

        return null;
    }

    // Asignación: i = i + 1;
    public function visitAsignacionStmt($ctx) {
        $id = $ctx->ID()->getText();
        $valor = $this->visit($ctx->expr());

        $this->environment->asignar($id, $valor);

        return null;
    }

    // Leer variable: print(i)
    public function visitId($ctx) {
        $id = $ctx->getText();

        return $this->environment->obtener($id);
    }


    // ==========================================
    // 3. VALORES PRIMITIVOS (Hojas del árbol)
    // ==========================================
    public function visitInt($ctx) {
        return intval($ctx->getText());
    }

    public function visitFloat($ctx) {
        return floatval($ctx->getText());
    }

    public function visitString($ctx) {
        $texto = $ctx->getText();
        return substr($texto, 1, -1);
    }

    // ==========================================
    // 7. FUNCIONES Y PROCEDIMIENTOS
    // ==========================================

    // Declaración: int sumar(int a, int b) { ... }
    public function visitFuncStmt($ctx) {
        $id = $ctx->ID()->getText();

        $funcion = [
            'tipo' => 'funcion_usuario',
            'contexto' => $ctx,
            'entorno_padre' => $this->environment
        ];

        $this->environment->guardar($id, $funcion);
        return null;
    }

    // Llamada suelta: saludar();
    public function visitCallStmt($ctx) {
        return $this->ejecutarLlamada($ctx);
    }

    // Llamada en expresión: int x = sumar(2, 2);
    public function visitLlamada($ctx) {
        return $this->ejecutarLlamada($ctx);
    }

    // Lógica central para invocar funciones
    private function ejecutarLlamada($ctx) {
        $id = $ctx->ID()->getText();
        $funcion = $this->environment->obtener($id);

        if (!is_array($funcion) || $funcion['tipo'] !== 'funcion_usuario') {
            throw new \Exception("Error: '$id' no es una funcion invocable.");
        }

        $ctxFuncion = $funcion['contexto'];

        // 1. Evaluar los argumentos que envió el usuario
        $valoresArgumentos = [];
        if ($ctx->expr() !== null) {
            // los argumentos son una lista de reglas 'expr'
            foreach ($ctx->expr() as $exprCtx) {
                $valoresArgumentos[] = $this->visit($exprCtx);
            }
        }

        // 2. Crear el nuevo entorno (Scope)
        $entornoAnterior = $this->environment;
        $esteEntorno = new \App\Custom\Environment($funcion['entorno_padre']);

        // 3. Emparejar parámetros con argumentos
        if ($ctxFuncion->parametros() !== null) {
            $nombresParams = $ctxFuncion->parametros()->ID(); // Lista de IDs de la gramática

            if (count($nombresParams) !== count($valoresArgumentos)) {
                throw new \Exception("Error: Numero incorrecto de argumentos para '$id'.");
            }

            for ($i = 0; $i < count($nombresParams); $i++) {
                $nombreParam = $nombresParams[$i]->getText();
                $esteEntorno->guardar($nombreParam, $valoresArgumentos[$i]);
            }
        } elseif (count($valoresArgumentos) > 0) {
            throw new \Exception("Error: La funcion '$id' no espera argumentos.");
        }

        // 4. Ejecutar la función en su propio entorno
        $this->environment = $esteEntorno;
        $resultado = $this->visit($ctxFuncion->bloque());

        // 5. Limpiar memoria y regresar al entorno anterior
        $this->environment = $entornoAnterior;

        // 6. Retornar el valor si hubo un 'return'
        if ($resultado instanceof \App\Custom\ReturnType) {
            return $resultado->retVal;
        }

        return null;
    }

    // Manejo del 'return'
    public function visitReturnStmt($ctx) {
        $valor = null;
        if ($ctx->expr() !== null) {
            $valor = $this->visit($ctx->expr());
        }
        return new \App\Custom\ReturnType($valor);
    }
    //

    // ==========================================
    // 8. ARREGLOS (ARRAYS 1D y 2D)
    // ==========================================

    // 1. Inicialización explícita: int[] arr = {1, 2, 3};
    public function visitArrayInit($ctx) {
        $elementos = [];
        if ($ctx->expr() !== null) {
            foreach ($ctx->expr() as $exprCtx) {
                $elementos[] = $this->visit($exprCtx);
            }
        }
        return $elementos;
    }

    // 2. Instanciación con tamaño: new int[5][5]
    public function visitArrayNew($ctx) {
        $dimensiones = [];
        foreach ($ctx->expr() as $exprCtx) {
            $valor = $this->visit($exprCtx);
            if (!is_int($valor) || $valor < 0) {
                throw new \Exception("Error: La dimension del arreglo debe ser un numero positivo.");
            }
            $dimensiones[] = $valor;
        }
        return $this->allocateArray($dimensiones, 0);
    }

    // Función recursiva para crear matrices de cualquier dimensión
    private function allocateArray($dimensiones, $nivel) {
        $tamano = $dimensiones[$nivel];
        if ($nivel === count($dimensiones) - 1) {
            return array_fill(0, $tamano, null);
        }
        $arreglo = [];
        for ($i = 0; $i < $tamano; $i++) {
            $arreglo[] = $this->allocateArray($dimensiones, $nivel + 1);
        }
        return $arreglo;
    }

    // 3. Lectura: print(arr[0][1]);
    public function visitArrayAccess($ctx) {
        $arreglo = $this->visit($ctx->expr(0)); // Evaluamos la base (ej: arr)
        $indice = $this->visit($ctx->expr(1));  // Evaluamos el índice (ej: 0)

        if (!is_array($arreglo)) {
            throw new \Exception("Error: Intento de indexar algo que no es un arreglo.");
        }
        if (!array_key_exists($indice, $arreglo)) {
            throw new \Exception("Error: Indice '$indice' fuera de rango.");
        }

        return $arreglo[$indice];
    }

    // 4. Escritura: arr[0][1] = 50;
    public function visitAsignacionArrayStmt($ctx) {
        $valorAsignar = $this->visit($ctx->expr(2)); // El valor a guardar (ej: 50)
        $indice = $this->visit($ctx->expr(1));       // El índice final (ej: 1)

        // Llamamos a una función auxiliar para modificar el arreglo en la memoria real
        $this->actualizarArreglo($ctx->expr(0), $indice, $valorAsignar);

        return null;
    }

    // Función auxiliar para rastrear y modificar matrices multidimensionales
    private function actualizarArreglo($baseCtx, $indice, $valor) {
        // Si la base es un ID directo (ej: arr[0] = 5)
        if ($baseCtx instanceof \App\Generated\Context\IdContext) {
            $nombreVar = $baseCtx->getText();
            $arregloReal = $this->environment->obtener($nombreVar);
            $arregloReal[$indice] = $valor;
            $this->environment->asignar($nombreVar, $arregloReal);
        }
        // Si la base es otro arreglo (ej: matriz[0][1] = 5)
        elseif ($baseCtx instanceof \App\Generated\Context\ArrayAccessContext) {
            $indiceAnterior = $this->visit($baseCtx->expr(1));
            $arregloReal = $this->visit($baseCtx);
            $arregloReal[$indice] = $valor;

            // Llamada recursiva hacia arriba para ir actualizando las capas del arreglo
            $this->actualizarArreglo($baseCtx->expr(0), $indiceAnterior, $arregloReal);
        } else {
            throw new \Exception("Error: Asignacion invalida a un arreglo.");
        }
    }
    // ==========================================
    // 9. CICLO FOR
    // ==========================================

    public function visitForStmt($ctx) {
        // 1. Crear un entorno local (Scope) específico para el ciclo For
        // Esto permite que 'int i = 0;' solo exista dentro del ciclo
        $entornoAnterior = $this->environment;
        $this->environment = new \App\Custom\Environment($entornoAnterior);

        // 2. Ejecutar la instrucción de inicialización (ej: int i = 0;)
        // Nota: En ANTLR, los elementos repetidos se agrupan en arreglos.
        $this->visit($ctx->instruccion(0));

        // 3. Evaluar la condición mientras sea verdadera (ej: i < 5)
        while ($this->visit($ctx->expr())) {

            // 4. Ejecutar el bloque de código
            $resultado = $this->visit($ctx->bloque());

            // 5. Manejo de señales (Break, Continue, Return)
            if ($resultado instanceof \App\Custom\BreakType) {
                break; // Romper el ciclo de PHP
            }
            if ($resultado instanceof \App\Custom\ReturnType) {
                $this->environment = $entornoAnterior; // Limpiar memoria
                return $resultado; // Propagar el return a la función padre
            }
            // Si es un Continue, simplemente dejamos que pase a la siguiente fase
            // que es la actualización de la variable.

            // 6. Ejecutar la instrucción de incremento/actualización (ej: i = i + 1;)
            $this->visit($ctx->instruccion(1));
        }

        // 7. Al terminar el ciclo, destruimos el entorno local para liberar memoria
        $this->environment = $entornoAnterior;
        return null;
    }

    // ==========================================
    // 5. OPERACIONES LÓGICAS Y RELACIONALES
    // ==========================================
    public function visitRelational($ctx) {
        $left = $this->visit($ctx->expr(0));
        $right = $this->visit($ctx->expr(1));
        $op = $ctx->op->getText();

        if ($op == '<') return $left < $right;
        if ($op == '>') return $left > $right;
        if ($op == '<=') return $left <= $right;
        if ($op == '>=') return $left >= $right;
        return false;
    }

    public function visitEquality($ctx) {
        $left = $this->visit($ctx->expr(0));
        $right = $this->visit($ctx->expr(1));
        $op = $ctx->op->getText();

        if ($op == '==') return $left == $right;
        if ($op == '!=') return $left != $right;
        return false;
    }

    public function visitAnd($ctx) {
        $left = $this->visit($ctx->expr(0));
        $right = $this->visit($ctx->expr(1));
        return $left && $right;
    }

    public function visitOr($ctx) {
        $left = $this->visit($ctx->expr(0));
        $right = $this->visit($ctx->expr(1));
        return $left || $right;
    }

    // ==========================================
    // 6. BLOQUES Y CONTROL DE FLUJO
    // ==========================================

    // ==========================================
    // MANEJO DEL BLOQUE MAESTRO (Scopes y Banderas)
    // ==========================================


    public function visitBloque($ctx) {
        $entornoAnterior = $this->environment;
        $this->environment = new Environment($entornoAnterior);

        if ($ctx->instrucciones() !== null) {
            foreach ($ctx->instrucciones()->instruccion() as $instruccion) {
                $resultado = $this->visit($instruccion);

                if ($resultado instanceof FlowType) {
                    $this->environment = $entornoAnterior;
                    return $resultado;
                }
            }
        }

        $this->environment = $entornoAnterior;
        return null;
    }

    public function visitBloqueStmt($ctx) {
        return $this->visit($ctx->bloque());
    }

    public function visitIfStmt($ctx) {
        // Evaluamos la condición
        $condicion = $this->visit($ctx->expr());

        if ($condicion) {
            // Si es verdadero, visitamos el primer bloque { }
            return $this->visit($ctx->bloque(0));
        } elseif ($ctx->bloque(1) !== null) {
            // Si es falso y existe un 'else' (segundo bloque), lo visitamos
            return $this->visit($ctx->bloque(1));
        }
        return null;
    }

    public function visitWhileStmt($ctx) {
        // El while de PHP debe evaluar la condición del while de Golampi
        while ($this->visit($ctx->expr())) {

            // Ejecutamos el bloque de instrucciones
            $resultado = $this->visit($ctx->bloque());

            // Verificamos si el bloque retornó una señal de flujo
            if ($resultado instanceof \App\Custom\BreakType) {
                break; // Rompe el ciclo while de PHP
            }

            if ($resultado instanceof \App\Custom\ContinueType) {
                continue; // Salta a la siguiente iteración de PHP
            }

            if ($resultado instanceof \App\Custom\ReturnType) {
                return $resultado; // Sale de la función
            }
        }
        return null;
    }

    // --- Banderas de Flujo ---
    public function visitBreakStmt($ctx) {
        return new BreakType();
    }

    public function visitContinueStmt($ctx) {
        return new ContinueType();
    }
}
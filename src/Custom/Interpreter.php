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

    // ¡Asegúrate de que se llame exactamente visitBloque!
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
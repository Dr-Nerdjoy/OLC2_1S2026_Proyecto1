<?php

namespace App\Custom;

use Exception;

class Environment {

    // Aquí guardamos las variables: ['nombre_variable' => valor]
    private $valores = [];

    private $anterior;

    public function __construct($anterior = null) {
        $this->anterior = $anterior;
    }

    // Guardar una variable (Declaración: int a = 10;)
    public function guardar($id, $valor) {
        // Validar si ya existe en este entorno actual para evitar redeclaración
        if (array_key_exists($id, $this->valores)) {
            throw new Exception("Error semántico: La variable '$id' ya existe en este ámbito.");
        }
        $this->valores[$id] = $valor;
    }

    // Actualizar una variable existente (Asignación: a = 20;)
    public function asignar($id, $valor) {
        // 1. Busco en mi entorno actual
        if (array_key_exists($id, $this->valores)) {
            $this->valores[$id] = $valor;
            return;
        }

        // 2. Si no está, busco en el entorno superior (el papá)
        if ($this->anterior != null) {
            $this->anterior->asignar($id, $valor);
            return;
        }

        // 3. Si no está en ningún lado -> Error
        throw new Exception("Error semántico: La variable '$id' no ha sido declarada.");
    }

    // Obtener el valor de una variable (Uso: print(a);)
    public function obtener($id) {
        if (array_key_exists($id, $this->valores)) {
            return $this->valores[$id];
        }

        if ($this->anterior != null) {
            return $this->anterior->obtener($id);
        }

        throw new Exception("Error semántico: La variable '$id' no existe.");
    }
}
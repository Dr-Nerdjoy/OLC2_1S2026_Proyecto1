# Manual Técnico - Intérprete Golampi ⚡

**Universidad de San Carlos de Guatemala** **Facultad de Ingeniería - Escuela de Ciencias y Sistemas** **Compiladores 2** **Estudiante:** Pablo Alejandro - 201708993

---

## 1. Introducción
El presente documento detalla la arquitectura, el diseño y las decisiones de implementación del intérprete para el lenguaje **Golampi**. Este proyecto fue desarrollado utilizando **PHP 8** como lenguaje anfitrión y **ANTLR4** como generador de analizadores léxicos y sintácticos. El intérprete se ejecuta en un entorno web que permite la edición de código, visualización de consola, reporte de errores y renderizado del Árbol de Sintaxis Abstracta (AST) en tiempo real.

## 2. Arquitectura del Compilador
El ciclo de vida de ejecución de Golampi sigue el modelo clásico de un intérprete basado en árboles:

1.  **Entrada de Código:** El usuario ingresa el código fuente a través de la interfaz web (IDE).
2.  **Análisis Léxico (Scanner):** El `GolampiLexer` generado por ANTLR convierte la cadena de texto en una secuencia de Tokens.
3.  **Análisis Sintáctico (Parser):** El `GolampiParser` evalúa la secuencia de Tokens contra la gramática definida y construye un Árbol de Parseo (Parse Tree).
4.  **Análisis Semántico y Ejecución:** A través del patrón de diseño **Visitor**, la clase `Interpreter.php` recorre el árbol nodo por nodo. Durante este recorrido se validan tipos, se manejan ámbitos de memoria (Scopes) y se ejecutan las instrucciones en tiempo real.

## 3. Gramática y Herramientas (Golampi.g4)
La gramática central del lenguaje fue diseñada en ANTLR4.
* **Archivos generados:** El proceso de construcción genera las clases de contexto (`Context`), el Lexer y el Parser en el namespace `App\Generated`.
* **Tipos de datos soportados:** `int`, `float`, `string`, `bool`, `char`, `void`.
* **Estructuras de control:** Soporte nativo para `if-else`, ciclos `while` y `for`.
* **Funciones:** Soporte para declaración de funciones, paso de parámetros, sentencias `return` y recursividad.

## 4. Manejo de Memoria (Tabla de Símbolos)
El manejo de memoria se realiza a través de la clase `Environment.php`. Esta clase simula los **Ámbitos (Scopes)** del lenguaje.
* **Encadenamiento de Entornos:** Cada vez que se ingresa a un bloque `{ }` (como una función o un ciclo), se instancia un nuevo objeto `Environment` que guarda una referencia a su entorno padre.
* Esto permite que las variables locales "oculten" a las globales y que la memoria local se destruya al finalizar el bloque (recolección de basura natural de PHP), previniendo colisiones de nombres.

## 5. Patrón Visitor (El Intérprete)
En lugar de incrustar código de ejecución directamente en la gramática, se optó por una separación de responsabilidades usando el patrón **Visitor** (`Interpreter.php`).
* Cada regla gramatical (ej. `visitAsignacionStmt`, `visitWhileStmt`) tiene un método homólogo en el intérprete.
* **Control de Flujo:** Para manejar interrupciones como `break`, `continue` o `return`, el intérprete utiliza clases envoltorio (Wrapper Classes) que propagan una señal hacia arriba en el árbol de llamadas de PHP hasta ser interceptadas por el bloque controlador (ej. el ciclo `while`).

## 6. Reporte de Errores
Se implementó un sistema robusto de captura de errores para evitar fallos silenciosos:
* **Léxicos y Sintácticos:** Se inyectó un `ErrorListener` personalizado en ANTLR para interceptar tokens no reconocidos o estructuras mal formadas antes de la ejecución.
* **Semánticos:** Si una variable no está definida o hay una incompatibilidad en tiempo de ejecución, el intérprete lanza una Excepción (ej. `throw new \Exception(...)`) que es capturada por el controlador web para ser mostrada en la tabla de reportes.

## 7. Generación del AST
Para la visualización gráfica del código analizado, se diseñó la clase `DotGenerator.php`, la cual realiza un recorrido recursivo sobre el árbol de ANTLR y genera un script en formato **Graphviz (DOT)**. Posteriormente, la librería web `Viz.js` renderiza este script generando un gráfico vectorial interactivo en el navegador del usuario.
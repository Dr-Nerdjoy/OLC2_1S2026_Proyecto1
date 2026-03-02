<?php
namespace App\Custom;

use Antlr\Antlr4\Runtime\Tree\ParseTree;
use Antlr\Antlr4\Runtime\Tree\TerminalNode;

class DotGenerator {
    private $dot = "";
    private $counter = 0;

    public function generate(ParseTree $tree): string {
        $this->dot = "digraph AST {\n";
        // Estilo general del árbol
        $this->dot .= "  node [shape=box, fontname=\"monospace\", style=filled, fillcolor=\"#2d2d2d\", fontcolor=\"#d4d4d4\", color=\"#444444\"];\n";
        $this->dot .= "  edge [color=\"#858585\"];\n";
        $this->dot .= "  bgcolor=\"transparent\";\n";

        $this->traverse($tree, $this->nextId());

        $this->dot .= "}\n";
        return $this->dot;
    }

    private function traverse(ParseTree $node, int $parentId) {
        // Si es una hoja (un token final como un número, ID, o símbolo)
        if ($node instanceof TerminalNode) {
            $label = str_replace('"', '\\"', $node->getText());
            // Escapamos caracteres especiales de HTML/Graphviz
            $label = htmlspecialchars($label, ENT_QUOTES);
            $this->dot .= "  node{$parentId} [label=\"{$label}\", fillcolor=\"#198754\", fontcolor=\"#ffffff\"];\n"; // Verde para las hojas
            return;
        }

        // Si es un nodo de regla (contexto intermedio)
        $ruleName = (new \ReflectionClass($node))->getShortName();
        $ruleName = str_replace('Context', '', $ruleName);
        $this->dot .= "  node{$parentId} [label=\"{$ruleName}\"];\n";

        // Recorremos los hijos recursivamente
        for ($i = 0; $i < $node->getChildCount(); $i++) {
            $child = $node->getChild($i);
            $childId = $this->nextId();

            // Creamos la conexión de padre a hijo
            $this->dot .= "  node{$parentId} -> node{$childId};\n";

            // Visitamos al hijo
            $this->traverse($child, $childId);
        }
    }

    private function nextId(): int {
        return ++$this->counter;
    }
}
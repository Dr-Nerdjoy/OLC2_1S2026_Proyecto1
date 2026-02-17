grammar Golampi;

// Regla inicial
start : (instruccion | funcion)* EOF;

instrucciones : instruccion* ;

// Permite anidar código

bloque : '{' instrucciones '}' ;


// Tipos de datos soportados
tipos   : 'int'
        | 'float'
        | 'string'
        | 'bool'
        | 'char'
        | 'void'
        ;

// Definición: int suma(int a, int b) { ... }
funcion : tipos ID '(' parametros? ')' bloque  # FuncStmt ;

// Lista de parámetros: int a, string b
parametros : tipos ID (',' tipos ID)* ;

instruccion : 'print' '(' expr ')' ';'              # PrintStmt
            | 'if' '(' expr ')' bloque
              ('else' bloque)?                      # IfStmt
            | 'while' '(' expr ')' bloque           # WhileStmt
            | 'for' '(' instruccion expr ';' instruccion ')' bloque  # ForStmt
            // Variables y Asignaciones
            | tipos ID '=' expr ';'                 # DeclaracionStmt
            | ID '=' expr ';'                       # AsignacionStmt
            | expr '[' expr ']' '=' expr ';'        # AsignacionArrayStmt

            // Control de Flujo
            | 'break' ';'                           # BreakStmt
            | 'continue' ';'                        # ContinueStmt
            | 'return' expr? ';'                    # ReturnStmt

            | bloque                                # BloqueStmt
            | ID '(' (expr (',' expr)*)? ')' ';'    # CallStmt
            ;

expr : expr '[' expr ']'                      # ArrayAccess
     | NEW tipos ('[' expr ']')+              # ArrayNew
     | '{' (expr (',' expr)*)? '}'            # ArrayInit
     | ID '(' (expr (',' expr)*)? ')'         # Llamada

     |  '!' expr                              # Not          // Nivel 1: Unario
     | '-' expr                               # Negacion     // Nivel 1: Unario (ej: -5)
     | expr op=('*'|'/'|'%') expr             # MulDiv       // Nivel 2: Multiplicación
     | expr op=('+'|'-') expr                 # AddSub       // Nivel 3: Suma
     | expr op=('<='|'>='|'<'|'>') expr       # Relational // Nivel 4: Comparación
     | expr op=('=='|'!=') expr               # Equality     // Nivel 5: Igualdad
     | expr op='&&' expr                      # And          // Nivel 6: Lógica AND
     | expr op='||' expr                      # Or           // Nivel 7: Lógica OR

     | INT                                    # Int          // Valores primitivos
     | FLOAT                                  # Float
     | STRING                                 # String
     | CHAR                                   # Char
     | 'true'                                 # True
     | 'false'                                # False
     | ID                                     # Id
     | '(' expr ')'                           # Parens
     ;

NEW : 'new';
PRINT   : 'print';
IF      : 'if';

// Tipos primitivos
INT : [0-9]+ ;
FLOAT   : [0-9]+ '.' [0-9]+ ;
STRING  : '"' (~["\r\n\\] | '\\' .)* '"';
CHAR    : '\'' . '\'' ;

ID      : [a-zA-Z_][a-zA-Z0-9_]* ;
WS  : [ \t\r\n]+ -> skip ; // espacio en blanco
COMMENT : '//' ~[\r\n]* -> skip ;
BLOCK_COMMENT : '/*' (. | [\r\n])*? '*/' -> skip ;
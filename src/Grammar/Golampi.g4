grammar Golampi;

// Regla inicial
start : (instruccion | funcion)* EOF;

instrucciones : instruccion* ;

bloque : '{' instrucciones '}' ; // Permite anidar código

tipos   : 'int'
        | 'float'
        | 'string'
        | 'bool'
        | 'char'
        | 'void'
        ;
instruccion : 'print' '(' expr ')' ';'              # PrintStmt
            | 'if' '(' expr ')' bloque
              ('else' bloque)?                      # IfStmt
            | 'while' '(' expr ')' bloque           # WhileStmt
            | tipos ID '=' expr ';'                 # DeclaracionStmt
            | ID '=' expr ';'                       # AsignacionStmt
            | 'for' '(' instruccion expr ';' instruccion ')' bloque  # ForStmt
            | 'break' ';'                           # BreakStmt
            | 'continue' ';'                        # ContinueStmt
            | 'return' expr? ';'                    # ReturnStmt
            | bloque                                # BloqueStmt  // Para soportar { } sueltos
            ;
// Definición de una función: tipo nombre ( params ) { codigo }
funcion : tipos ID '(' parametros? ')' bloque  # FuncStmt ;

// Lista de parámetros: int a, float b...
parametros : tipos ID (',' tipos ID)* ;

expr : '!' expr                       # Not          // Nivel 1: Unario
     | '-' expr                       # Negacion     // Nivel 1: Unario (ej: -5)
     | expr op=('*'|'/'|'%') expr     # MulDiv       // Nivel 2: Multiplicación
     | expr op=('+'|'-') expr         # AddSub       // Nivel 3: Suma
     | expr op=('<='|'>='|'<'|'>') expr # Relational // Nivel 4: Comparación
     | expr op=('=='|'!=') expr       # Equality     // Nivel 5: Igualdad
     | expr op='&&' expr              # And          // Nivel 6: Lógica AND
     | expr op='||' expr              # Or           // Nivel 7: Lógica OR
     | INT                            # Int          // Valores primitivos
     | FLOAT                          # Float
     | STRING                         # String
     | CHAR                           # Char
     | 'true'                         # True
     | 'false'                        # False
     | ID '(' (expr (',' expr)*)? ')'     # Llamada
     | ID                             # Id
     | '(' expr ')'                   # Parens
     ;

INT : [0-9]+ ;
FLOAT   : [0-9]+ '.' [0-9]+ ;
STRING  : '"' .*? '"' ;       // Cadenas con comillas dobles
ID      : [a-zA-Z_][a-zA-Z0-9_]* ; // Nombres de variables (ej: edad, numero_1)
CHAR    : '\'' . '\'' ;
WS  : [ \t\r\n]+ -> skip ; // espacio en blanco
COMMENT : '//' ~[\r\n]* -> skip ;
BLOCK_COMMENT : '/*' .*? '*/' -> skip ;
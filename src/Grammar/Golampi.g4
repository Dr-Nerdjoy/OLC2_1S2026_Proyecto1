grammar Golampi;

// Regla inicial
start : instrucciones EOF;

instrucciones : instruccion* ;

instruccion : 'print' '(' expr ')' ';'  # PrintStmt
            ;

expr : expr op=('*'|'/') expr           # MulDiv
     | expr op=('+'|'-') expr           # AddSub
     | INT                              # Int
     | '(' expr ')'                     # Parens
     ;

INT : [0-9]+ ;
WS  : [ \t\r\n]+ -> skip ;
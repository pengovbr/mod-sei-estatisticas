JSON_FMT='{"name":"Módulo Estatísticas", "version": "%s", "compatible_with": [%s]}'
VERSAO_MODULO=$(grep 'const VERSAO_MODULO' src/MdEstatisticas.php | cut -d'"' -f2)
VERSOES=$(sed -n -e "/COMPATIBILIDADE_MODULO_SEI = \[/,/;/ p" src/MdEstatisticas.php \
           | sed -e '1d;$d' | sed -e '/\/\//d' \
           | sed -e "s/'/\"/g"| tr -d '\r'| tr -d ' ')

printf "$JSON_FMT" "$VERSAO_MODULO" "$VERSOES" > compatibilidade.json

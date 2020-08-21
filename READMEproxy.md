# Módulo Estatísticas do SEI

## Conexão via Proxy

Caso o seu servidor use proxy para acessar a internet o php e o apache deverão estar configurados para usarem esse proxy de preferência de forma transparente. 

Há casos em que essa configuração ou não foi feita ou existe algum impedimento técnico para a realização da mesma.

Nesse caso há a possibilidade, a partir da versão 1.1.3, de indicar nos parâmetros de configuração um servidor proxy e a porta.

Segue um exemplo de como ficará o array de configuração do módulo com as chaves proxy e proxyPort:

```

'MdEstatisticas' => array(
      'url' => 'https://estatistica.processoeletronico.gov.br',
      'sigla' => 'MPOG',
      'chave' => '123456',
      'proxy' => 'meuproxy.gov.br',
      'proxyPort'=> '8080',
      'ignorar_arquivos' => array('sei/temp', 'sei/config/ConfiguracaoSEI.php', 'sei/config/ConfiguracaoSEI.exemplo.php', '.vagrant', '.git')),
      
``` 
**Importante:**
- Atenção acima a variável proxyPort (com o segundo P maiúsculo)
- A configuração acima apenas deverá ser usada quando concluir-se que o seu apache/php não está conseguindo acessar a internet usando a configuração transparente de proxy do servidor


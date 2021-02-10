# Módulo Estatísticas do SEI

Este módulo, ao ser executado, via agendamento ou manualmente:
- coleta informações estatísticas diversas como: quantidade de documentos e processos, quantidade de unidades, quantidade de usuários, percentual de tipos de documentos, etc
- coleta o hash dos arquivos fontes do SEI e módulos instalados - o intuito aqui é segurança. Para que o órgão tenha ciência dos arquivos que possam ter sido alterados ou adicionados sem a sua autorização. Importante! Esses dados são tratados como privados
- os dados públicos de estatísticas aparecem em http://processoeletronico.gov.br - procurar pelo painel **Indicadores Negociais** - há um delay na atualização do painel que ocorre todos os dias por volta de 6h
- os dados de hash serão disponibilizados aos gestores do SEI através de um acesso controlado que será oportunamente divulgado


## Instalação
Faça o download desse projeto no seguinte diretório do SEI
```
cd sei/web/modulos
git clone https://github.com/spbgovbr/mod-sei-estatisticas.git
```

Para que o SEI reconheça esse módulo é necessário editar o arquivo *sei/config/ConfiguracaoSEI.php*.
Adicione a propriedade *Modulos* ao objeto *SEI*, caso nao exista, e como valor um array contendo o nome do módulo e o nome do diretório do módulo. **'Modulos' => array('MdEstatisticas' => 'mod-sei-estatisticas')**
```
...
  'SEI' => array(
      ...
      'Modulos' => array('MdEstatisticas' => 'mod-sei-estatisticas')),
...
  ```
Ainda editando o arquivo *sei/config/ConfiguracaoSEI.php* adicione uma nova chave com as configurações do módulo.
Os campos url, sigla e chave devem ser preenchidos com os valores enviados pela equipe do Pen.

Detalhes sobre o campo **ignorar_arquivos**:

- Esse campo atua durante a leitura de hash dos fontes do SEI

- Via de regra, o ideal é deixar apenas o SEI em uma pasta isolada no apache. Porém existem instalações onde o SEI é compartilhado na mesma pasta com outros ativos

- Nesse caso, o módulo ao ler as informações do diretório onde o SEI se encontra, poderá encontrar arquivos sem permissão de leitura ou até mesmo pastas imensas, como a pasta para armazenar os arquivos externos

- Para mitigar esse problema basta informar as pastas para o módulo ignorar a leitura do hash

- Para isso, vamos usar o campo ignorar_arquivos. Deve ser preenchido com os diretórios a serem ignorados durante a leitura da coleta de hash

- Caso seja necessário, basta adicionar as pastas a serem ignoradas seguindo o formato do array php, como abaixo

```
...
  'SEI' => array(
      'URL' => getenv('SEI_HOST_URL').'/sei',
      'Producao' => false,
      'RepositorioArquivos' => '/var/sei/arquivos',
      'Modulos' => array('MdEstatisticas' => 'mod-sei-estatisticas')),
...
  'MdEstatisticas' => array(
      'url' => 'https://estatistica.processoeletronico.gov.br',
      'sigla' => 'MPOG',
      'chave' => '123456',
      'ignorar_arquivos' => array('sei/temp', 'sei/config/ConfiguracaoSEI.php', 'sei/config/ConfiguracaoSEI.exemplo.php', '.vagrant', '.git')),


...
  ```

Abaixo seguem algumas campos (chaves) opcionais que possam ser necessárias ao seu ambiente:

- **Proxy:**
	caso a saída para a internet do servidor use proxy, e você encontre problema de conexão [clique aqui](READMEproxy.md) 

- **Diretório de Arquivos Externos muito grande**
	caso o seu diretório de arquivos externos seja muito grande, ou tenha permissões especiais, pode ser que o php demore muito tempo para calcular o seu tamanho ou simplesmente não consiga calcular usando suas funções nativas. Nesse caso basta ativar uma chave ao arquivo de configuração informando para o php executar um comando nativo do linux. Dessa forma ele usará o comando "du -s -b caminhododirexterno" para calcular o tamanho. Abaixo exemplo dessa chave ativada ( ver abaixo a chave filesystemdu)
```
...
  'SEI' => array(
      'URL' => getenv('SEI_HOST_URL').'/sei',
      'Producao' => false,
      'RepositorioArquivos' => '/var/sei/arquivos',
      'Modulos' => array('MdEstatisticas' => 'mod-sei-estatisticas')),
...
  'MdEstatisticas' => array(
      'url' => 'https://estatistica.processoeletronico.gov.br',
      'sigla' => 'MPOG',
      'chave' => '123456',
      'filesystemdu' => true,
      'ignorar_arquivos' => array('sei/temp', 'sei/config/ConfiguracaoSEI.php', 'sei/config/ConfiguracaoSEI.exemplo.php', '.vagrant', '.git')),

...
```

Em seguida basta criar um agendamento definindo-se a periodicidade do envio. O agendamento deverá executar o seguinte comando:

 ```
MdEstatisticasAgendamentoRN::coletarIndicadores
 ```



### Script de Validação do Módulo

A partir da versão 1.1.0, disponibilizamos um script para ser executado via linha de comando. Esse script irá executar algumas validações básicas no intuito de levantar possíveis problemas de instalação e configuração do módulo. O script:
- testa o arquivo de configuração pelas chaves do módulo
- testa se os atributos url, sigla e chave estão preenchidos
- executa um teste de conexão usando as credenciais acima
- executa um teste de leitura do hash usando as configurações do módulo 

Para executar o script basta acessar a pasta do sei e rodar o script q fica na pasta script do módulo, por exemplo:
```
cd /opt
php sei/web/modulos/mod-sei-estatisticas/scripts/verifica_instalacao.php
```
o resultado deverá ser algo como:
```
00001 - [02/07/2020 16:00:43]   INICIANDO VERIFICACAO DA INSTALACAO DO MODULO MOD-SEI-ESTATISTICAS:
00002 - [02/07/2020 16:00:44]       - Modulo corretamente ativado no arquivo de configuracao do sistema
00003 - [02/07/2020 16:00:45]       - Chaves obrigatorias no arquivo de configuracao estao preenchidas (url,sigla e chave)
00004 - [02/07/2020 16:00:46]       - Conexao com o WebService realizada com sucesso
00005 - [02/07/2020 16:00:47]       - Vamos agora iniciar a leitura dos hashs.
00006 - [02/07/2020 16:00:47]         Se necessario, certifique-se de ler e entender na documentacao do repositorio sobre a variavel opcional ignorar_arquivos,
00007 - [02/07/2020 16:00:47]         caso junto do sei voce tenha na pasta do Apache outros diretorios ou sistemas.
00008 - [02/07/2020 16:00:47]             Ressalva: prestar atencao ao usuario que esta executando esse script pois ao ler os arquivos via agendamento quem
00009 - [02/07/2020 16:00:47]             executa sera o user do crontab e via web sera o apache
00010 - [02/07/2020 16:00:47]       - Aguardando 20 segs antes de iniciar a leitura. Aguarde...
00011 - [02/07/2020 16:01:07]       - Iniciando leitura agora, aguarde...
00012 - [02/07/2020 16:01:10]       - Leitura de Hashs realizada
00013 - [02/07/2020 16:01:10]       - Foi calculado o hash de 6167 arquivos.
00014 - [02/07/2020 16:01:10]
00015 - [02/07/2020 16:01:10]   ** VERIFICACAO DA INSTALACAO DO MODULO DE ESTATISTICAS FINALIZADA COM SUCESSO **
```

IMPORTANTE:
- verificar se há rota aberta do servidor do SEI onde roda o agendamento para o servidor Webservice coletor
- a rota pode ser facilmente verificada usando, por exemplo, o comando:
```
curl https://estatistica.processoeletronico.gov.br
 ```
o resultado deverá ser algo como:
```
{"sistema":"WebService Estatísticas do SEI","versao":"1.0.0"}
 ```

## Suporte
Caso precise de ajuda, ou para solicitar a sua chave de conexão, favor abrir um chamado em nossa Central de Atendimento:
http://processoeletronico.gov.br/index.php/conteudo/suporte. A categoria do chamado é PEN - MODULO ESTATISTICAS - INSTALAÇÃO.
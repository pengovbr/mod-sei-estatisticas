# Módulo Estatísticas do SEI

Este módulo, ao ser executado, via agendamento ou manualmente:
- coleta informações estatísticas diversas como: quantidade de documentos e processos, quantidade de unidades, quantidade de usuários, percentual de tipos de documentos, etc.
- coleta o hash dos arquivos fontes do SEI e módulos instalados - o intuito aqui é segurança. Para que o órgão tenha ciência dos arquivos que possam ter sido alterados ou adicionados sem a sua autorização. Importante! Esses dados são tratados como privados.
- os dados públicos de estatísticas aparecem no [painel de estatísticas do gestor](https://paineis.processoeletronico.gov.br/?view=governanca) - há um delay na atualização do painel, que ocorre todos os dias por volta de 6h.
- os dados de hash serão disponibilizados aos gestores do SEI através de um acesso controlado que será oportunamente divulgado.

## Compatibilidade

Este módulo é compatível com o SEI4.0.x, SEI4.1.x, SEI5.0.x e Super1.x 


## Instalação
Faça o download desse projeto no seguinte diretório do SEI:
```
cd sei/web/modulos
git clone https://github.com/pengovbr/mod-sei-estatisticas.git
```

Ou, se preferir baixe o zip da release desejada: https://github.com/pengovbr/mod-sei-estatisticas/releases

Ao final, os códigos do módulo deverão estar localizados na pasta **/sei/web/modulos/mod-sei-estatisticas**.

Para que o SEI reconheça esse módulo é necessário editar o arquivo */sei/config/ConfiguracaoSEI.php*, adicionando o nome do módulo e o nome do diretório criado acima na subchave 'Modulos' da chave 'SEI':
```
...
  'SEI' => array(
      ...
      'Modulos' => array('MdEstatisticas' => 'mod-sei-estatisticas')),
...
  ```
Ainda editando o arquivo */sei/config/ConfiguracaoSEI.php*, adicione uma nova chave com as configurações do módulo. As chaves: url, sigla e chave são obrigatórias, as demais são opcionais, podem ou não existir no seu arquivo.
```
...
  'MdEstatisticas' => array(
      'url' => 'https://estatistica.processoeletronico.gov.br', 
      'sigla' => 'MPOG', 
      'chave' => '123456', 
      'filesystemdu' => false,  //chave opcional ver detalhes abaixo
      'ignorarLeituraAnexos' => false, //chave opcional ver detalhes abaixo
      'tamanhoFs' => '', //chave opcional ver detalhes abaixo
      'proxy' => '',  //chave opcional ver detalhes abaixo
      'proxyPort'=> '',  //chave opcional ver detalhes abaixo
      'ignorar_arquivos' => array('sei/temp', 'sei/config/ConfiguracaoSEI.php', 'sei/config/ConfiguracaoSEI.exemplo.php', '.vagrant', '.git'),
      ),
...
  ```

## Campos da chave de configuração 'MdEstatisticas'
- Os campos **url**, **sigla** e **chave** devem ser preenchidos com os valores informados pela equipe do PEN, a partir de Chamado aberto na Central de Atendimento.
- Campo **filesystemdu**:
  - Alterar para **true** caso seu diretório de arquivos externos seja muito grande ou tenha permissões especiais e o php demore muito tempo para calcular o seu tamanho ou simplesmente não consiga calcular usando suas funções nativas. Nesse caso, o php executará um comando nativo do Linux para calcular o tamanho (du -s -b caminhododirexterno).

- Campo **ignorarLeituraAnexos:**
  - em alguns casos, a medição do tamanho do diretório de arquivos anexos do SEI é extremamente demorada ou impossível, devido a alguma falha de infra do NFS ou algum lock no mesmo. Isso acontece de forma até nem tão incomum em instalações com gigantescas quantidades de arquivos ou partições muito grandes. Sendo assim, o módulo não conseguirá medir o tamanho desse diretório fazendo-se necessário desabilitar essa medição e setando o tamanho na chave abaixo. Uma dica para saber se a sua instalação tem esse problema é rodar o comando: "du -s -b caminhododirexterno", onde caminhododirexterno é a chave RepositorioArquivos (arquivos anexos do SEI) localizada no arquivo ConfiguracaoSEI.php. Se esse comando for extremamente demorado para concluir ou nunca concluir, significa que o módulo também não irá ler usando as funções de php ou do linux. Para setar o módulo para ignorar essa leitura coloque esse campo como **true** e também sete o valor da chave abaixo

- Campo **tamanhoFs:**
  - caso o campo acima (ignorarLeituraAnexos) tenha sido setado como true, então obrigatoriamente você deve informar o tamanho do diretório de anexos do SEI aqui nessa chave. O valor deve ser informado em bytes, desta forma: suponha que o seu dir seja de 21Tb, nesse caso coloque 23089744183296
  - 21Tb em bytes fica: 21 * 1024 * 1024 * 1024 * 1024

- Campo **proxy**:
  - Preencher com o endereço do proxy caso seu servidor use proxy para acessar a internet.
  - Nesse caso, o php e o apache deverão estar configurados para usarem esse proxy de preferência de forma transparente.
  - Há casos em que essa configuração ou não foi feita ou existe algum impedimento técnico para sua realização.
  - Essa configuração deve ser utilizada somente depois que concluir que o seu servidor não está conseguindo acessar a internet usando a configuração transparente de proxy.
- Campo **proxyPort**:
  - Preencher com a Porta do proxy caso seu servidor use proxy para acessar a internet.
  - Deve ser utilizado em conjunto com o campo anterior **proxy**.
  - Atenção para o nome correto do campo 'proxyPort', com o segundo "P" maiúsculo.
- Campo **ignorar_arquivos**:
  - Esse campo atua durante a leitura de hash dos fontes do SEI.
  - Via de regra, o ideal é deixar apenas o SEI em uma pasta isolada no apache. Porém existem instalações onde o SEI é compartilhado na mesma pasta com outros ativos.
  - Nesse caso, o módulo ao ler as informações do diretório onde o SEI se encontra, poderá encontrar arquivos sem permissão de leitura ou até mesmo pastas imensas, como a pasta para armazenar os arquivos externos.
  - Para mitigar esse problema basta informar os arquivos ou pastas para o módulo ignorar a leitura do hash, conforme exemplo acima.

Em seguida, basta criar um agendamento no SEI, no menu Infra > Agendamentos, definindo-se a periodicidade do envio dos dados para o servidor do Webservice coletor. O agendamento deverá executar o seguinte comando:
 ```
MdEstatisticasAgendamentoRN::coletarIndicadores
 ```

## Script de Validação do Módulo

O módulo possui um script que realiza diversas validações para listar possíveis problemas de instalação ou configuração:
- testa o arquivo de configuração pelas chaves do módulo.
- testa se os atributos url, sigla e chave estão preenchidos.
- executa um teste de conexão usando as credenciais acima.
- executa um teste de leitura do hash usando as configurações do módulo.
- executa uma coleta e envio dos dados passo a passo.

Para executar o script de validação, basta rodar o script localizado na pasta de códigos do módulo:
```
cd /opt
php sei/web/modulos/mod-sei-estatisticas/scripts/verifica_instalacao.php
```
O resultado deverá ser algo como:
```
00001 -     INICIANDO VERIFICACAO DA INSTALACAO DO MODULO MOD-SEI-ESTATISTICAS:
00002 -         - Modulo corretamente ativado no arquivo de configuracao do sistema
00003 -         - Chaves obrigatorias no arquivo de configuracao estao preenchidas (url,sigla e chave)
00004 -         - Conexao com o WebService realizada com sucesso
00005 -         - Vamos agora iniciar a leitura dos hashs.
00006 -           Se necessario, certifique-se de ler e entender na documentacao do repositorio sobre a variavel opcional ignorar_arquivos,
00007 -           caso junto do sei voce tenha na pasta do Apache outros diretorios ou sistemas.
00008 -               Ressalva: prestar atencao ao usuario que esta executando esse script pois ao ler os arquivos via agendamento quem
00009 -               executa sera o user do crontab e via web sera o apache
00010 -         - Aguardando 20 segs antes de iniciar a leitura. Aguarde...
00011 -         - Iniciando leitura agora, aguarde...
00012 -         - Leitura de Hashs realizada
00013 -         - Foi calculado o hash de 6942 arquivos.
00014 -
00015 -     ** VERIFICACAO INICIAL DA INSTALACAO DO MODULO DE ESTATISTICAS FINALIZADA **
00016 -
00017 -
00018 -
00019 -
00020 -     AGORA VAMOS TENTAR ENVIAR UMA LEITURA COMPLETA PARA O WEB SERVICE COLETOR:
00021 -         Autenticar no WebService
00022 -         Autenticado. Coletando indicadores
00023 -         Indicadores coletados, enviando
00024 -         Indicadores recebidos. Coletar indicadores do tipo lista
00025 -         Obter a data do ultimo envio das quantidades de acessos
00026 -         Ultima data das quantidades de acessos: 2021-02-10. Coletar quantidade de acessos
00027 -         Coletado. Enviar quantidade de acessos:
00028 -         Enviado. Coletar velocidades por cidade:
00029 -         Coletado. Enviar:
00030 -         Enviado. Obter a data do ultimo envio das quantidades de navegadores
00031 -         Ultima data das quantidades de navegadores: 2016-11-30. Coletar quantidade de navegadores
00032 -         Coletado. Enviar:
00033 -         Enviado. Coletar a quantidade de logs de erro:
00034 -         Coletado. Enviar:
00035 -         Enviado. Obter a ultima data que foi enviado a quantidade de recursos
00036 -         Ultima data das quantidades de recursos: 2016-10-31. Coletar quantidade de recursos
00037 -         Coletado. Enviar:
00038 -         Enviado. Coletar o hash dos arquivos do SEI:
00039 -         Coletado. Enviar:
00040 -         Enviado:
00041 -         Apenas Coletar velocidades por cidade novamente
00042 -         Coletado
00043 -     FINALIZADO COM SUCESSO. NAO ESQUECA DE AGENDAR NO MENU INFRA -> AGENDAMENTO DO SEI E VERIFICAR SE O AGENDAMENTO ESTA RODANDO
```

IMPORTANTE:
- verificar se há rota aberta do servidor do SEI onde roda o agendamento para o servidor do Webservice coletor.
- a rota pode ser facilmente verificada usando o comando:
```
curl https://estatistica.processoeletronico.gov.br
 ```
O resultado deverá ser algo como:
```
{"sistema":"WebService Estatísticas do SEI","versao":"1.0.0"}
 ```
## Suporte
1. Por favor enviar sua chave pública para que possamos enviar de forma segura as credenciais. [Como criar seu par de chaves pública/privada](https://manual-roteiro-integracao-login-unico.servicos.gov.br/pt/stable/chavepgp.html).
2.  Para solicitar a sua chave de conexão ou caso precise de ajuda, favor [abrir chamado em nossa Central de Atendimento](https://portaldeservicos.gestao.gov.br/pt#/).

> [!IMPORTANT]
> A categoria do chamado é **Processo Eletrônico Nacional -> Módulo de Estatística -> Solicitação**.

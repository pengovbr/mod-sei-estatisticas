# Módulo Estatísticas do SEI

## Instalação
Faça o download desse projeto no seguinte diretório do SEI
```
cd sei/web/modulos
git clone http://softwarepublico.gov.br/gitlab/mp/mod-sei-estatisticas.git
```

Para que o SEI reconheça esse módulo é necessário editar o arquivo *sei/sei/config/ConfiguracaoSEI.php*.
Adicione a propriedade *Modulos* ao objeto *SEI*, caso nao exista, e como valor um array contendo o nome do módulo e o nome do diretório do módulo. **'Modulos' => array('MdEstatisticas' => 'mod-sei-estatisticas')**
```
...
  'SEI' => array(
      ...
      'Modulos' => array('MdEstatisticas' => 'mod-sei-estatisticas')),
...
  ```
Ainda editando o arquivo *sei/sei/config/ConfiguracaoSEI.php* adicione uma nova chave com as configurações do módulo
```
...
  'SEI' => array(
      'URL' => getenv('SEI_HOST_URL').'/sei',
      'Producao' => false,
      'RepositorioArquivos' => '/var/sei/arquivos',
      'Modulos' => array('MdEstatisticas' => 'mod-sei-estatisticas')),
...
  'MdEstatisticas' => array(
      'url' => 'http://estatisticas.planejamento.gov.br/estatisticas'),
...
  ```

## Como contribuir

### 1. Com Vagrant

Para o desenvolvimento é necessário ter instalado

- [Vagrant](https://www.vagrantup.com/)
- [VirtualBox](https://www.virtualbox.org/)

Na raiz do projeto SEI, crie o arquivo *Vagrantfile* com o seguinte conteúdo
```
Vagrant.configure("2") do |config|
    config.vm.box = "processoeletronico/sei-3.0.0"
end
```
Siga as instruções de instalação do módulo

Inicie o SEI com o comando.
 ```
sudo vagrant up
 ```
É necessário executar como administrador (root) porque a box está configurado para iniciar na porta 80.
Será feito o download da box e no final o projeto poderá ser acessivel no endereço.
 ```
http://localhost/sei
 ```

### 2. Com docker

É necessário ter instalado
- [Docker](https://docs.docker.com/install/)
- [Docker Compose](https://docs.docker.com/compose/install/)

Siga as orientações para instalar o módulo no SEI, acesse o diretório do módulo e execute
```
docker-compose up -d
```
Será feito download dos containers e no final o SEI estará acessivel em
 ```
http://localhost/sei
 ```


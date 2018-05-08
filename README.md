# Módulo Estatísticas do SEI

## Instalação
Faça o download desse projeto no seguinte diretório do SEI
```
cd sei/web/modulos
git clone http://softwarepublico.gov.br/gitlab/mp/mod-sei-estatisticas.git
```

Edite o arquivo *sei/sei/config/ConfiguracaoSEI.php* e adicione o nome do projeto e seu diretório na propriedade *Modulos*.
```
...

  'SEI' => array(
      'URL' => 'http://localhost/sei',
      'Producao' => false,
      'RepositorioArquivos' => '/var/sei/arquivos',
      'Modulos' => array('MdEstatisticas' => 'mod-sei-estatisticas')),

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


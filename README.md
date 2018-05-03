# Módulo Estatísticas do SEI

## Como contribuir

Para o desenvolvimento é necessário ter instalado

- [Vagrant](https://www.vagrantup.com/)
- [VirtualBox](https://www.virtualbox.org/)

Faça o download do projeto SEI e na raiz crie o arquivo *Vagrantfile* com o seguinte conteúdo
```
Vagrant.configure("2") do |config|
config.vm.box = "processoeletronico/sei-3.0.0"
end
```
Faça o download desse projeto no seguinte diretório do SEI
```
cd sei/web/modulos
git clone http://softwarepublico.gov.br/gitlab/mp/mod-sei-estatisticas.git
```

Edite o arquivo *sei/sei/config/ConfiguracaoSEI.php* e adicione a propriedade *Modulos*, caso não exista, com o nome desse módulo.
```
...

  'SEI' => array(
      'URL' => 'http://localhost/sei',
      'Producao' => false,
      'RepositorioArquivos' => '/var/sei/arquivos',
      'Modulos' => array('MdEstatisticas' => 'mod-sei-estatisticas')),

...
  ```

Inicie o SEI com o comando

 ```
vagrant up
 ```



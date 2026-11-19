<?php

class ConfiguracaoModEstatisticas extends InfraConfiguracao {

  private static $instance;

  public static function getInstance(){
    if (ConfiguracaoModEstatisticas::$instance == null) {
      ConfiguracaoModEstatisticas::$instance = new ConfiguracaoModEstatisticas();
    }
    return ConfiguracaoModEstatisticas::$instance;
  }

  public function getArrConfiguracoes(){
    return [

      'MdEstatisticas' => [
        'url' => 'trocar_para_url',
        'sigla' => 'trocar_para_sigla',
        'chave' => 'trocar_para_chave',
        'filesystemdu' => false,
        'ignorarLeituraAnexos' => false,
        'tamanhoFs' => '',
        'proxy' => '',
        'proxyPort'=> '',
        'ignorar_arquivos' => ['sei/temp', 'sei/config/ConfiguracaoSEI.php',
           'sei/config/ConfiguracaoSEI.exemplo.php', '.vagrant', '.git'],
      ],
    ];
  }
}
?>

<?php

class ConfiguracaoSip extends InfraConfiguracao {

  private static $instance;

  public static function getInstance(){
    if (ConfiguracaoSip::$instance == null) {
      ConfiguracaoSip::$instance = new ConfiguracaoSip();
    }
    return ConfiguracaoSip::$instance;
  }

  public function getArrConfiguracoes(){
    return [
      'Sip' => [
        'URL' => getenv('HOST_URL').'/sip',
        'Producao' => false,
        'NumLoginSemCaptcha' => 3,
      ],

      'PaginaSip' => [
        'NomeSistema' => 'SIP',
        'NomeSistemaComplemento' => '',
      ],

      'SessaoSip' => [
        'SiglaOrgaoSistema' => 'ABC',
        'SiglaSistema' => 'SIP',
        'PaginaLogin' => getenv('HOST_URL') . '/sip/login.php',
        'SipWsdl' => getenv('HOST_URL') . '/sip/controlador_ws.php?servico=sip',
        'ChaveAcesso' => getenv('SIP_CHAVE_ACESSO'),
        'https' => false,
      ],

      'BancoSip'  => [
        'Servidor' => getenv('DATABASE_HOST'),
        'Porta' => getenv('DATABASE_PORT'),
        'Banco' => getenv('SIP_DATABASE_NAME'),
        'Usuario' => getenv('SIP_DATABASE_USER'),
        'Senha' => getenv('SIP_DATABASE_PASSWORD'),
        'Tipo' => getenv('DATABASE_TYPE'),
        'PesquisaCaseInsensitive' => false,
      ],

      'CacheSip' => [
        'Servidor' => 'memcached',
        'Porta' => '11211',
        'Timeout' => 2,
        'Tempo' => 3600,
      ],

      'InfraMail' => [
        'Tipo' => '2',
        'Servidor' => 'smtp',
        'Porta' => '1025',
        'Codificacao' => '8bit',
        'Autenticar' => false,
        'Usuario' => '',
        'Senha' => '',
        'Seguranca' => '',
        'MaxDestinatarios' => 25,
        'MaxTamAnexosMb' => 15,
        'Protegido' => '',
      ],
    ];
  }
}
?>

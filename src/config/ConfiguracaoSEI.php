<?php

class ConfiguracaoSEI extends InfraConfiguracao {

  private static $instance;

  public static function getInstance(){
    if (ConfiguracaoSEI::$instance == null) {
      ConfiguracaoSEI::$instance = new ConfiguracaoSEI();
    }
    return ConfiguracaoSEI::$instance;
  }

  public function getArrConfiguracoes(){
    return [
      'SEI' => [
        'URL' => getenv('HOST_URL').'/sei',
        'Producao' => false,
        'DigitosDocumento' => 7,
        'PermitirAcessoLocalPdf' => '',
        'NumLoginUsuarioExternoSemCaptcha' => 3,
        'TamSenhaUsuarioExterno' => 8,
        'DebugWebServices' => 0,
        'RepositorioArquivos' => '/var/sei/arquivos',
        'Modulos' => [
          'MdEstatisticas' => 'mod-sei-estatisticas'
        ],
      ],

      'PaginaSEI' => [
          'NomeSistema' => 'SUPER',
          'NomeSistemaComplemento' => SEI_VERSAO,
          'LogoMenu' => '',
          'OrgaoTopoJanela' => 'S',
      ],

      'SessaoSEI' => [
        'SiglaOrgaoSistema' => 'ABC',
        'SiglaSistema' => 'SEI',
        'PaginaLogin' => getenv('HOST_URL') . '/sip/login.php',
        'SipWsdl' => getenv('HOST_URL') . '/sip/controlador_ws.php?servico=sip',
        'ChaveAcesso' => getenv('SEI_CHAVE_ACESSO'),
        'https' => false,
      ],

      'BancoSEI'  => [
        'Servidor' => getenv('DATABASE_HOST'),
        'Porta' => getenv('DATABASE_PORT'),
        'Banco' => getenv('SEI_DATABASE_NAME'),
        'Usuario' => getenv('SEI_DATABASE_USER'),
        'Senha' => getenv('SEI_DATABASE_PASSWORD'),
        'Tipo' => getenv('DATABASE_TYPE'),
        'PesquisaCaseInsensitive' => false,
      ],

      'CacheSEI' => [
        'Servidor' => 'memcached',
        'Porta' => '11211',
        'Timeout' => 1,
        'Tempo' => 3600,
      ],

      'Solr' => [
        'Servidor' => 'http://solr:8983/solr',
        'CoreProtocolos' => 'sei-protocolos',
        'CoreBasesConhecimento' => 'sei-bases-conhecimento',
        'CorePublicacoes' => 'sei-publicacoes',
        'TempoCommitProtocolos' => 10,
        'TempoCommitBasesConhecimento' => 60,
        'TempoCommitPublicacoes' => 60,
      ],

      'JODConverter' => [
        'Servidor' => 'http://jod/converter/service'
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

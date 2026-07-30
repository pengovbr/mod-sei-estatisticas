<?

class ConfiguracaoModEstatisticas extends InfraConfiguracao {

  private static $instance = null;

  public static function getInstance(){
    if (ConfiguracaoModEstatisticas::$instance == null) {
      ConfiguracaoModEstatisticas::$instance = new ConfiguracaoModEstatisticas();
    }
    return ConfiguracaoModEstatisticas::$instance;
  }

  public function getArrConfiguracoes(){
    return array(

      'MdEstatisticas' => array(
        'url' => getenv('ESTATISTICAS_URL'),
        'sigla' => getenv('ESTATISTICAS_SIGLA'),
        'chave' => getenv('ESTATISTICAS_CHAVE'),
        'filesystemdu' => false,
        'ignorarLeituraAnexos' => false,
        'tamanhoFs' => '',
        'proxy' => '',
        'proxyPort'=> '',
        'ignorar_arquivos' => array('sei/temp', 'sei/config/ConfiguracaoSEI.php', 'sei/config/ConfiguracaoSEI.exemplo.php', '.vagrant', '.git'),
      ),
    );
  }
}
?>

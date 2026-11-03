<?
/**
 * TRIBUNAL REGIONAL FEDERAL DA 4ª REGIÃO
 *
 * 29/04/2016 - criado por mga@trf4.jus.br
 *
 */


class MdEstatisticas extends SeiIntegracao{
  const VERSAO_MODULO = "2.0.2";

  const COMPATIBILIDADE_MODULO_SEI = [
    '5.0.0', '5.0.1', '5.0.2', '5.0.3', '5.0.4', '5.0.5'
  ];

  public function __construct(){
  }

  public function getNome(){
    return 'Módulo de Estatisticas do SEI';
  }

  public function getVersao() {
    return $this::VERSAO_MODULO;
  }

  public function getInstituicao(){
    return 'Ministério da Gestão e da Inovação em Serviços Públicos - MGI';
  }

  public function inicializar($strVersaoSEI)
  {      
    if (!defined('DIR_SEI_WEB')) {
      define('DIR_SEI_WEB', realpath(DIR_SEI_CONFIG.'/../web'));
    }

    $this->carregarArquivoConfiguracaoModulo(DIR_SEI_CONFIG);
    return true;
  }

  private function carregarArquivoConfiguracaoModulo($strDiretorioSeiWeb){
    try{
        $strArquivoConfiguracao = $strDiretorioSeiWeb . '/mod-sei-estatisticas/ConfiguracaoModEstatisticas.php';
        include_once $strArquivoConfiguracao;       
    } catch(Exception $e){
        LogSEI::getInstance()->gravar("Arquivo de configuração do módulo Estatisticas não pode ser localizado em " . $strArquivoConfiguracao);
    }
  }
}
?>

<?
require_once dirname(__FILE__).'/../../../SEI.php';


class MdEstatisticasColetarRN extends InfraRN {

  public function __construct(){
    parent::__construct();
  }

  protected function inicializarObjInfraIBanco(){
    return BancoSEI::getInstance();
  }

  public function coletarIndicadores() {

    try {

      InfraDebug::getInstance()->setBolLigado(true);
      InfraDebug::getInstance()->setBolDebugInfra(false);
      InfraDebug::getInstance()->setBolEcho(false);
      InfraDebug::getInstance()->limpar();

      //IMPLEMENTAÇÃO DA EXTRAÇÃO DE INDICADORES
      //1) Criar o objeto DTO representativo dos indicaores
      $objIndicadoresDTO = new IndicadoresDTO();

      //2) Preencher cada indicador do sistema
      $objIndicadoresDTO->setStrVersaoSEI($this->obterVersaoSEI());
      $objIndicadoresDTO->setNumTamanhoFileSystem($this->obterTamanhoFileSystem());
      $objIndicadoresDTO->setStrPlugins($this->obterPlugins());
      $objIndicadoresDTO->setNumQuantidadeUnidades($this->obterQuantidadeUnidades());
      $objIndicadoresDTO->setNumTamanhoDocumentosExternos($this->obterTamanhoTotalDocumentosExternos());

      //...

      //3) Salvar indicador no banco de dados ????
      //

      //4) Enviar indicadores para webservice
      //

      LogSEI::getInstance()->gravar(InfraDebug::getInstance()->getStrDebug(),InfraLog::$INFORMACAO);
      return $objIndicadoresDTO;

    } catch(Exception $e) {
      InfraDebug::getInstance()->setBolLigado(false);
      InfraDebug::getInstance()->setBolDebugInfra(false);
      InfraDebug::getInstance()->setBolEcho(false);
      throw new InfraException('Erro processando estatísticas do sistema.',$e);
    }
  }

  private function obterVersaoSEI(){
    InfraDebug::getInstance()->gravar('SEI01 - Versão SEI: ' . SEI_VERSAO, InfraLog::$INFORMACAO);
    return SEI_VERSAO;
  }

  private function getDirectorySize($path){
    $bytestotal = 0;
    $path = realpath($path);
    if($path!==false){
      foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object){
        $bytestotal += $object->getSize();
      }
    }
    return $bytestotal;
  }

  private function obterTamanhoFileSystem(){
    $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();
    if ($objConfiguracaoSEI->isSetValor('SEI', 'RepositorioArquivos')){
      $diretorio = $objConfiguracaoSEI->getValor('SEI','RepositorioArquivos');
      $tamanho = $this->getDirectorySize($diretorio);

      InfraDebug::getInstance()->gravar('SEI02 - Diretorio: ' . $diretorio, InfraLog::$INFORMACAO);
      InfraDebug::getInstance()->gravar('SEI02 - Tamanho File System: ' . $tamanho, InfraLog::$INFORMACAO);
    }
    return $tamanho;
  }

  private function obterPlugins(){
    global $SEI_MODULOS;
    $lista = array();
    foreach($SEI_MODULOS as $strModulo => $seiModulo){
      $result = array(
        'nome' => $strModulo,
        'versao' => $seiModulo->getVersao()
      );
      array_push($lista, $result);
    }
    $resultado = json_encode($lista);

    InfraDebug::getInstance()->gravar('SEI03 - Plugins: ' . $resultado, InfraLog::$INFORMACAO);
    return $resultado;
  }

  private function obterQuantidadeUnidades(){

    $objUnidadeRN = new UnidadeRN();
    $numQuantidadeUnidades = $objUnidadeRN->contarRN0128(new UnidadeDTO());

    InfraDebug::getInstance()->gravar('SEI11 - Quantidade Unidades: ' . $numQuantidadeUnidades, InfraLog::$INFORMACAO);
    return $numQuantidadeUnidades;
  }

  private function obterTamanhoTotalDocumentosExternos(){

    $query = "select sum(tamanho) as tamanho from anexo where sin_ativo = 'S'";
    $rs = BancoSEI::getInstance()->consultarSql($query);
    $tamanho = (count($rs) && isset($rs[0]['tamanho'])) ? $rs[0]['tamanho'] : 0;

    InfraDebug::getInstance()->gravar('SEI12 - Tamanho Documentos Externos: ' . $tamanho, InfraLog::$INFORMACAO);
    return $tamanho;
  }

}
?>

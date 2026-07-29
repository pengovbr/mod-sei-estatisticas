<?
/**
 * TRIBUNAL REGIONAL FEDERAL DA 4ª REGIÃO
 *
 * 29/04/2016 - criado por mga@trf4.jus.br
 *
 */


class MdEstatisticas extends SeiIntegracao{
  const VERSAO_MODULO = "2.0.1";

  const COMPATIBILIDADE_MODULO_SEI = [
    '4.0.12','4.0.12.15','4.1.1','4.1.2','4.1.3','4.1.4','4.1.5','5.0.0'
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

  public function inicializar($strVersaoSEI){
    /*
    if (substr($strVersaoSEI, 0, 2) != '3.'){
      die('Módulo "'.$this->getNome().'" ('.$this->getVersao().') não é compatível com esta versão do SEI ('.$strVersaoSEI.').');
    }
     */
  }
}
?>

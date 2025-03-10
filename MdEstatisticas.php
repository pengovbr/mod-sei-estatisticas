<?
/**
 * TRIBUNAL REGIONAL FEDERAL DA 4� REGI�O
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
    return 'M�dulo de Estatisticas do SEI';
  }

  public function getVersao() {
    return $this::VERSAO_MODULO;
  }

  public function getInstituicao(){
    return 'Minist�rio da Gest�o e da Inova��o em Servi�os P�blicos - MGI';
  }

  public function inicializar($strVersaoSEI){
    /*
    if (substr($strVersaoSEI, 0, 2) != '3.'){
      die('M�dulo "'.$this->getNome().'" ('.$this->getVersao().') n�o � compat�vel com esta vers�o do SEI ('.$strVersaoSEI.').');
    }
     */
  }
}
?>

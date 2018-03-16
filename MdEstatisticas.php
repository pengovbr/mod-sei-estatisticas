<?
/**
 * TRIBUNAL REGIONAL FEDERAL DA 4ª REGIÃO
 *
 * 29/04/2016 - criado por mga@trf4.jus.br
 *
 */

 /*
 No SIP criar os recursos md_abc_processo_processar, md_abc_documento_processar e md_abc_andamento_lancar e adicionar em um novo perfil chamado MD_ABC_Básico.
  */

class MdEstatisticas extends SeiIntegracao{

  public function __construct(){
  }

  public function getNome(){
    return 'Módulo de Estatisticas do SEI';
  }

  public function getVersao() {
    return '1.0.0';
  }

  public function getInstituicao(){
    return 'MPDG - Ministério do Planejamento, Desenvolvimento e Gestão';
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

<?php
require_once dirname(__FILE__) . '/../../../SEI.php';

// Garante que código abaixo foi executado unicamente via linha de comando
if ($argv && $argv[0] && realpath($argv[0]) === __FILE__) {
    InfraDebug::getInstance()->setBolLigado(true);
    InfraDebug::getInstance()->setBolDebugInfra(false);
    InfraDebug::getInstance()->setBolEcho(true);
    InfraDebug::getInstance()->limpar();

    $resultado = 0;

    $fnPrint = function($strMensagem, $numIdentacao=0) {
        DebugPen::getInstance()->gravar($strMensagem, $numIdentacao, false, false);
    };


    try {
        SessaoSEI::getInstance(false);
        
        $fnPrint("INICIANDO VERIFICAÇÃO DA INSTALAÇÃO DO MÓDULO MOD-SEI-ESTATISTICAS:", 0);

        try{
            $objMdEstatisticasVerificarRN = new MdEstatisticasVerificarRN();
        }catch(Exception $e){
            $fnPrint("- Nao foi possível instanciar a classe, verifique se o modulo esta configurado no ConfiguracaoSEI.php. Maiores detalhes do erro abaixo", 1);
        }

        sleep(1);
        if($objMdEstatisticasVerificarRN->verificarAtivacaoModulo()){
            $fnPrint("- Módulo corretamente ativado no arquivo de configuracao do sistema", 1);
        }


        sleep(1);
        if($objMdEstatisticasVerificarRN->verificarArquivoConfiguracao()){
            $fnPrint("- Chaves obrigatorias no arquivo de configuracao estao preenchidas (url,sigla e chave) ", 1);
        }

        sleep(1);
        if($objMdEstatisticasVerificarRN->verificarConexao()){
            $fnPrint("- Conexão com o WebService realizada com sucesso", 1);
        }

        sleep(1);
        $fnPrint("- Vamos agora iniciar a leitura dos hashs." , 1);
        $fnPrint("  Certifique-se de ler e entender na documentacao do repositorio sobre a variavel opcional ignorar_arquivos, ", 1);
        $fnPrint("  caso junto do sei você tenha na pasta do Apache outros diretórios ou sistemas. ", 1);
        $fnPrint("- Aguardando 20 segs antes de iniciar a leitura. Aguarde...", 1);
        sleep(20);
        $fnPrint("- Iniciando leitura agora, aguarde... ", 1);
        
        $r = $objMdEstatisticasVerificarRN->verificarLeituraHashs();        

        if(is_array($r)){
            $fnPrint("- Leitura de Hashs realizada", 1);
            
            $fnPrint("- Foi calculado o hash de " . count($r) . " arquivos. ", 1);
        }else{
            throw new InfraException("Falha ao ler o hash dos arquivos. Verifique permissoes no diretorio e também a necessidade de configurar a variavel ignorar_arquivos");           
        }

        $fnPrint("", 0);
        $fnPrint("** VERIFICACAO DA INSTALACAO DO MODULO DE ESTATISTICAS FINALIZADA COM SUCESSO **", 0);

        exit(0);
    } finally {
        InfraDebug::getInstance()->setBolLigado(false);
        InfraDebug::getInstance()->setBolDebugInfra(false);
        InfraDebug::getInstance()->setBolEcho(false);
    }
}

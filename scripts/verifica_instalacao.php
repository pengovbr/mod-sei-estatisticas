<?php
require_once dirname(__FILE__) . '/../../../SEI.php';

// Garante que codigo abaixo foi executado unicamente via linha de comando
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
        
        $fnPrint("INICIANDO VERIFICACAO DA INSTALACAO DO MODULO MOD-SEI-ESTATISTICAS:", 0);

        try{
            $objMdEstatisticasVerificarRN = new MdEstatisticasVerificarRN();
        }catch(Exception $e){
            $fnPrint("- Nao foi possivel instanciar a classe, verifique se o modulo esta configurado no ConfiguracaoSEI.php. Maiores detalhes do erro abaixo", 1);
        }

        sleep(1);
        if($objMdEstatisticasVerificarRN->verificarAtivacaoModulo()){
            $fnPrint("- Modulo corretamente ativado no arquivo de configuracao do sistema", 1);
        }


        sleep(1);
        if($objMdEstatisticasVerificarRN->verificarArquivoConfiguracao()){
            $fnPrint("- Chaves obrigatorias no arquivo de configuracao estao preenchidas (url,sigla e chave) ", 1);
        }

        sleep(1);
        if($objMdEstatisticasVerificarRN->verificarConexao()){
            $fnPrint("- Conexao com o WebService realizada com sucesso", 1);
        }

        sleep(1);
        $fnPrint("- Vamos agora iniciar a leitura dos hashs." , 1);
        $fnPrint("  Se necessario, certifique-se de ler e entender na documentacao do repositorio sobre a variavel opcional ignorar_arquivos, ", 1);
        $fnPrint("  caso junto do sei voce tenha na pasta do Apache outros diretorios ou sistemas. ", 1);
        $fnPrint("  Ressalva: prestar atencao ao usuario que esta executando esse script pois ao ler os arquivos via agendamento quem", 2);
        $fnPrint("  executa sera o user do crontab e via web sera o apache ", 2);
        $fnPrint("- Aguardando 20 segs antes de iniciar a leitura. Aguarde...", 1);
        sleep(20);
        $fnPrint("- Iniciando leitura agora, aguarde... ", 1);
        
        $r = $objMdEstatisticasVerificarRN->verificarLeituraHashs();        

        if(is_array($r)){
            $fnPrint("- Leitura de Hashs realizada", 1);
            
            $fnPrint("- Foi calculado o hash de " . count($r) . " arquivos. ", 1);
        }else{
            throw new InfraException("Falha ao ler o hash dos arquivos. Verifique permissoes no diretorio e tambem a necessidade de configurar a variavel ignorar_arquivos");           
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

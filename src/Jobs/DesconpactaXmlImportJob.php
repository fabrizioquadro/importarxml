<?php

namespace fabrizioquadro\importarxml\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use fabrizioquadro\importarxml\Models\Import;
use fabrizioquadro\importarxml\Models\Import_Arquivo;
use fabrizioquadro\importarxml\Jobs\InsereXmlDbJob;

class DesconpactaXmlImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //vamos puxar todos os registros na tabela que estão para serem descompactados
        $imports = Import::where('STATUS', 'Processando')
        ->where('ANDAMENTO', 'Em Fila de Descompactação')
        ->get();

        foreach ($imports as $import){
            $dadosErro = [
                'STATUS' => "ERRO",
                'RETORNO' => "Não foi possível fazer a descompactação dos dados do arquivo enviado",
            ];

            Import::where('ID', $import->ID)->update($dadosErro);

            //vamos tentar descompactar o arquivo
            $zip = new \ZipArchive();
            $zip->open(public_path('importsXML/'.$import->ID_EMPRESA."/".$import->PASTA."/".$import->NM_ARQUIVO));

            $destino = public_path('importsXML/'.$import->ID_EMPRESA."/".$import->PASTA."/");

            if($zip->extractTo($destino) == true){
                $dados = [
                    'STATUS' => "Processando",
                    'ANDAMENTO' => "Inserindo Xmls no Banco de Dados",
                    'RETORNO' => "",
                ];

                Import::where('ID', $import->ID)->update($dados);

                InsereXmlDbJob::dispatch($import->ID);
            }

        }
    }
}

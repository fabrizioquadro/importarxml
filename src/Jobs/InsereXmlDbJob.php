<?php

namespace fabrizioquadro\importarxml\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use fabrizioquadro\importarxml\Models\Import;
use fabrizioquadro\importarxml\Models\Import_Arquivo;
use fabrizioquadro\importarxml\Jobs\AnalisaXmlProdutoSericoJob;
use fabrizioquadro\importarxml\Jobs\AnaliseXmlDestinatario;

class InsereXmlDbJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected $ID_IMPORT)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $import = Import::where('ID', $this->ID_IMPORT)->first();

        if($import->STATUS == "Processando" && $import->ANDAMENTO == "Inserindo Xmls no Banco de Dados"){
            //vamos ler os arquivos da pasta
            $destino = public_path('importsXML/'.$import->ID_EMPRESA."/".$import->PASTA."/");

            $arquivos = scandir($destino);

            $teste = "";

            $dados = [
                'ID_IMPORT' => $import->ID,
                'ID_EMPRESA' => $import->ID_EMPRESA,
                'STATUS' => 'Fila de Processamento',
            ];

            $contadorArquivos = count($arquivos) - 3;

            foreach($arquivos as $arquivo) {
                if($arquivo != "." && $arquivo != '..' && $arquivo != $import->NM_ARQUIVO){
                    $dados['ARQUIVO'] = $arquivo;
                    Import_Arquivo::create($dados);
                }
            }

            //vamos verificar se foram inseridos todos os arquivos no BD
            $contadorInserts = Import_Arquivo::where('ID_IMPORT', $import->ID)->count();

            if($contadorInserts == $contadorArquivos){
                $dados = [
                    "ANDAMENTO" => 'Em Fila de Processamento'
                ];

                Import::where('ID', $import->ID)->update($dados);

                logs()->critical('Vai Inserir no log');

                if($import->TP_IMPORT == "Produtos e Serviços"){
                    AnalisaXmlProdutoSericoJob::dispatch($import->ID);
                }
                elseif($import->TP_IMPORT == 'Destinatários'){
                    AnaliseXmlDestinatario::dispatch($import->ID);
                    logs()->critical('Inserindo '.$import->ID);

                }
                /*
                elseif($imprt->TP_IMPORT == "Ambos"){
                    AnalisaXmlProdutoSericoJob::dispatch($import->ID);
                    AnaliseXmlDestinatario::dispatch($import->ID);
                }
                */
            }
        }
    }
}

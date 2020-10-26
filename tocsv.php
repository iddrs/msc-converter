<?php

use function ptk\fs\join_path;
use function ptk\tabular\read_csv;
use function ptk\tabular\write_csv;

require 'vendor/autoload.php';
require 'config.php';
require 'transform.php';

//carrega a msc
$handle = fopen($msc_file, 'r');
$msc = read_csv($handle, ';', true, 1);
fclose($handle);
//print_r($msc);

//balancete contábil
echo "Criando o Balancete Contábil:", PHP_EOL;
//cria a estrutura de dados convertidos
$bal_cont_data = [];

//inicia loop pelos dados

foreach ($msc as $id => $row){
    $bal_cont_data[$id]['ContaContabil'] = transf_cc($row['ContaContabil']);
    $bal_cont_data[$id]['Valor'] = $row['Valor'];
    $bal_cont_data[$id]['ValorF'] = number_format($row['Valor'], 2, ',', '.');
    $bal_cont_data[$id]['TipoValor'] = $row['TipoValor'];
    $bal_cont_data[$id]['NaturezaValor'] = $row['NaturezaValor'];
    
    //inicialização das informações complementares
    $bal_cont_data[$id]['PoderOrgao'] = null;
    $bal_cont_data[$id]['FinanceiroPermanente'] = null;
    $bal_cont_data[$id]['DividaConsolidada'] = null;
    $bal_cont_data[$id]['FonteRecurso'] = null;
    $bal_cont_data[$id]['ComplementoFonteRecurso'] = null;
    $bal_cont_data[$id]['NaturezaReceita'] = null;
    $bal_cont_data[$id]['NaturezaDespesa'] = null;
    $bal_cont_data[$id]['FuncaoSubfuncao'] = null;
    $bal_cont_data[$id]['AnoInscricaoRestosAPagar'] = null;
    $bal_cont_data[$id]['DespesasMDEeASPS'] = null;
    
    //identifica as informações complementares
    for($i = 1; $i <= 7; $i++){
        $colname = "TipoInformacaoComplementar$i";
        $TipoIC = $row[$colname];
        $ValorIC = $row["InformacaoComplementar$i"];
//        var_dump($ValorIC);exit();
        switch ($TipoIC){
            case 'PO':
                $destiny = 'PoderOrgao';
                break;
            case 'FP':
                $destiny = 'FinanceiroPermanente';
                break;
            case 'DC':
                $destiny = 'DividaConsolidada';
                break;
            case 'FR':
                $destiny = 'FonteRecurso';
                $ValorIC = transf_fr($ValorIC);
                break;
            case 'CF':
                $destiny = 'ComplementoFonteRecurso';
                break;
            case 'NR':
                $destiny = 'NaturezaReceita';
                $ValorIC = transf_nro($ValorIC);
                break;
            case 'ND':
                $destiny = 'NaturezaDespesa';
                $ValorIC = transf_ndo($ValorIC);
                break;
            case 'FS':
                $destiny = 'FuncaoSubfuncao';
                $ValorIC = transf_fs($ValorIC);
                break;
            case 'AI':
                $destiny = 'AnoInscricaoRestosAPagar';
                break;
            case 'ES':
                $destiny = 'DespesasMDEeASPS';
                break;
        }
//        var_dump($destiny);exit();

        $bal_cont_data[$id][$destiny] = $ValorIC;
//        print_r($output);exit();
    }
}
//print_r($output);

//salva o conteúdo
$handle = fopen(join_path($output_dir, $bal_cont_file), 'w');
write_csv($handle, $bal_cont_data, ';', true);
fclose($handle);

echo "\tlinhas criadas: ", sizeof($bal_cont_data), PHP_EOL;

//balancete da receita
echo "Criando o Balancete da Receita:", PHP_EOL;

$bal_rec_data = [];


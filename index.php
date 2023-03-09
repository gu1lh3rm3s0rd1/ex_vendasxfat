<?php include 'conexaofaturamentodiario.php' ?>
<?php include 'conexaosomaanual.php' ?>
<?php include 'conexaovendadiaria.php' ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="refresh" content="60; url=index.php">
  <link rel="stylesheet" href="index.css">
  <title>Analise de Vendas</title>
  <!-- BIBLIOTECAS NECESSARIAS PARA USAR OS RECURSOS DO JAVASCRIPT -->
  <script type="text/javascript" src="https://www.google.com/jsapi"></script>
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
  <script type="text/javascript">
    google.charts.load("current", {packages:['corechart']});
    google.charts.setOnLoadCallback(drawChart);
    function drawChart() {
      var data = google.visualization.arrayToDataTable([
          [' ', 'VENDAS', 'FATURAMENTO'],

            //SELECIONA DADOS
            <?php
            include 'conexao.php';
                //NESSA CONSULTA FOI USADO AS FUNCOES DE SUB-QUERIES E JOINS ENTRE TABELAS, PARA TRANSFORMAR AS COLUNAS NOS DADOS NECESSARIOS PARA OBTER A INFORMAÇAO DESEJADA
                //FOI CRIADA UMA TABELA TEMPORAIA PARA OBTER AS COLUNAS DOS MESES EM TEMPO REAL
                //ESSA CONSULTA RETORNA A SOMA DE RENDA DA EMRPESA DE ACORDO COM O MES ATUAL DESDE O INICIO DO ANO ATUAL  
              
                $consulta = oci_parse($conexao, "SELECT UPPER(M.NOME_MES) AS MES, 
                    NVL(TRIM(LPAD(TO_CHAR(FAT.TOTAL_FAT, '999G999G999D99'), 8)), 0) AS FAT, 
                    NVL(TRIM(LPAD(TO_CHAR(VEND.TOTAL_VEND, '999G999G999D99'), 8)), 0) AS VEND 
                FROM ( SELECT TO_CHAR(ADD_MONTHS(TRUNC(TO_DATE('01/2023','MM/YYYY'), 'mm'), ROWNUM-1), 'mm') AS MES,
                TO_CHAR(ADD_MONTHS(TRUNC(TO_DATE('01/2023','MM/YYYY'), 'mon'), ROWNUM-1), 'mon') AS NOME_MES 
                FROM USER_TABLES WHERE ROWNUM <= MONTHS_BETWEEN(TO_DATE('12/2024','mm/yyyy'), TO_DATE('12/2023','mm/yyyy'))
                ) M
                LEFT OUTER JOIN
                ( 
                SELECT X2.MES AS MES, SUM(X2.TOTAL) AS TOTAL_FAT FROM ( SELECT X.DIA, X.NUM_MES AS MES, TO_CHAR(SUM(X.TOTAL), '999999999') AS TOTAL FROM
                ( SELECT EXTRACT(YEAR FROM TO_DATE(F2_EMISSAO,'YYYYMMDD'))AS ANO,
                    TO_CHAR(TO_DATE(F2_EMISSAO,'YYYYMMDD'),'MM') AS NUM_MES,
                    TO_CHAR(TO_DATE(F2_EMISSAO,'YYYYMMDD'),'DD') AS DIA,
                    TO_CHAR(TO_DATE('01/2023','mm/yyyy'), 'YYYYMMDD') AS JAN, 
                    F2_EMISSAO AS DATA, F2_VALFAT AS TOTAL
                FROM SF2010 WHERE SF2010.D_E_L_E_T_ = ' ' AND EXTRACT(YEAR FROM TO_DATE(F2_EMISSAO,'YYYYMMDD')) = EXTRACT(YEAR FROM SYSDATE)
                ) X WHERE X.DATA BETWEEN X.JAN AND TO_CHAR(SYSDATE, 'YYYYMMDD') GROUP BY X.DIA, X.NUM_MES ORDER BY X.NUM_MES, X.DIA ) X2 GROUP BY X2.MES
                ) FAT ON M.MES = FAT.MES
                LEFT OUTER JOIN
                ( 
                SELECT Y2.MES AS MES, SUM(Y2.TOTAL) AS TOTAL_VEND FROM ( SELECT Y.DIA, Y.NUM_MES AS MES, TO_CHAR(SUM(Y.TOTAL), '999999999') AS TOTAL FROM 
                ( SELECT EXTRACT(YEAR FROM TO_DATE(C5_EMISSAO,'YYYYMMDD')) AS ANO,
                    TO_CHAR(TO_DATE(C5_EMISSAO,'YYYYMMDD'),'MM') AS NUM_MES,
                    TO_CHAR(TO_DATE(C5_EMISSAO,'YYYYMMDD'),'DD') AS DIA,
                    TO_CHAR(TO_DATE('01/2023','mm/yyyy'), 'YYYYMMDD') AS JAN, 
                    C5_EMISSAO AS DATA, C6_VALOR AS TOTAL
                FROM SC6010 INNER JOIN SC5010 ON C6_FILIAL = C5_FILIAL AND C6_NUM = C5_NUM AND SC5010.D_E_L_E_T_ = ' ' AND SC6010.D_E_L_E_T_ = ' '
                INNER JOIN SF4010 ON F4_CODIGO = C6_TES AND SF4010.D_E_L_E_T_ = ' ' WHERE C5_FILIAL = '01' AND F4_DUPLIC = 'S' AND C5_TIPO = 'N' AND C6_BLQ <> 'R'
                AND EXTRACT(YEAR FROM TO_DATE(C5_EMISSAO,'YYYYMMDD')) = EXTRACT(YEAR FROM SYSDATE)
                ) Y WHERE Y.DATA BETWEEN Y.JAN AND TO_CHAR(SYSDATE, 'YYYYMMDD') GROUP BY Y.DIA, Y.NUM_MES ORDER BY Y.NUM_MES, Y.DIA ) Y2 GROUP BY Y2.MES
                ) VEND ON M.MES = VEND.MES ORDER BY M.MES");

                oci_execute($consulta);         

                //PREPARA ARRAYS PARA SELECIONAR AS COLUNAS DESEJADAS 
                $mes = '';
                $total_v3nd4 = '';
                $total_f4t = '';

                //SELECIONAMOS OS DADOS QUE QUEREMOS
                while ($dados = oci_fetch_array($consulta)){
                $meses = $mes . '' . $dados['MES'] . '';
                $vend = $total_v3nd4 . '' . $dados['VEND'] . '';
                $fat = $total_f4t . '' . $dados['FAT'] . '';

            ?>

            ['<?php echo $meses ?>', <?php echo $vend ?>, <?php echo $fat ?>], 

            <?php } 
            
            error_reporting(0);
            
            ?>

            ]);

            //FORMATANDO OS VALORES DAS COLUNAS DO GRAFICO 
        var formatter = new google.visualization.NumberFormat({
                //decimalSymbol: '.',
                //groupingSymbol: '.',
                suffix: ' M',
                prefix: ' R$'
            });
                // Estou aplicando para as colunas 1 e 2
                formatter.format(data, 1);
                formatter.format(data, 2);       

                //CHAMA OS ANNOTATES/ROTULO DOS DADOS EO INSERE ACIMA DE CADA COLUNA DO GRAFICO
        var view = new google.visualization.DataView(data);
        view.setColumns([0, 1,
                        { calc: "stringify",
                            sourceColumn: 1,
                            type: "string",
                            role: "annotation"
                            },
                        2, 
                        { calc: "stringify",
                            sourceColumn: 2,
                            type: "string",
                            role: "annotation"
                            }
                        ]);

            var options = {
                    colors: ['#313B6C', '#F7D52A'],
                    bar: {
                    groupWidth: "75%"
                    },
                    legend: {
                    position: 'top',
                    alignment: 'center',
                    },
                    annotations: {
                        alwaysOutside: true,
                        textStyle: {
                            fontSize: 9.5,
                            color: 'black'
                        }
                    },
                    chartArea: {
                        width: '87.5%', 
                        height: '400px'
                    },
                    series: {
                        2: {
                            targetAxisIndex:0
                        }
                    },
                    vAxis: {
                        ticks: [0, 5, 10, 15, 20, 25]
                    }
                };

        var chart = new google.visualization.ColumnChart(document.getElementById("columnchart_values"));
        chart.draw(view, options);
        }

    </script>
</head>
<body>  
    <!-- ENQUADRAMOS O GRAFICO INTEIRO DENTRO DO MAIN PARA PODER CONSTRUIR UM AUTO-REDIMENSIONAMENTO/RESPONSIVIDADE -->
    <main>
            <div class="cards">
                <div class="cartao">
                <center> <h1>TOTAL VENDIDO DIARIO</h1> </center>
                    <center> <h2> <?php echo ' R$ ' . $totaldiariovenda ?> </h2> </center>
                </div>
                <div class="cartao">
                <center> <h1>TOTAL VENDIDO ANUAL</h1> </center>
                    <center> <h2> <?php echo ' R$ ' . $totalvenda ?> </h2> </center>
                </div>
                <div class="logo">
                <center><img src="imagem/LOGO SEM FUNDO.png" alt="logo"></center>
                </div>
                <div class="cartao">
                <center> <h1>TOTAL FATURADO DIARIO</h1> </center>
                    <center> <h2> <?php echo ' R$ ' . $totaldiariofat ?> </h2> </center>
                </div>
                <div class="cartao">
                <center> <h1>TOTAL FATURADO ANUAL</h1> </center>
                    <center> <h2> <?php echo ' R$ ' . $totalfat ?> </h2> </center>
                </div>
            </div>
            <div>
            <?php
                function bd_nice_number($n) {
                    // first strip any formatting;
                    $n = (0+str_replace(",","",$n));
                    
                    // is this a number?
                    if(!is_numeric($n)) return false;
                    
                    // now filter it;
                    if($n>1000000000000) return round(($n/1000000000000),1).' trillion';
                    else if($n>1000000000) return round(($n/1000000000),1).' billion';
                    else if($n>1000000) return round(($n/1000000),1).' million';
                    else if($n>1000) return round(($n/1000),1).' thousand';
                    
                    return number_format($n);
                    echo $n;
                }
            ?>
            </div>
            <div id="columnchart_values" style="width: 100%; min-width: 200px; max-width: 1080px; height: 400px;"></div>        
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-pprn3073KE6tl6bjs2QrFaJGz5/SUsLqktiwsUTF55Jfv3qYSDhgCecCxMW52nD2" crossorigin="anonymous"></script>
</body>
</html>
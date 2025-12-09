<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Logs</title>
    <link rel="icon" type="image/png" href="../images/CHMSU.png">
    <link rel="stylesheet" href="../css/guest_style.css">
    <script type="text/javascript" src="../script/app.js" defer></script> 
    <script type="text/javascript" src="../script/guest_logs_script.js" defer></script>
    <script src="../script/drill_alert_popup.js"></script> 
</head>

<body>
    <?php include 'guest_nav.php'; ?>  
    <main class="table" id="customers_table">
        <section class="table__header">
            <h1>Your Logs</h1>
            <div class="input-group">
                <input type="search" placeholder="Search Data...">
                <img src="../../icons/search.png" alt="">
            </div>
            <div class="export__file">
                <label for="export-file" class="export__file-btn" title="Export File"> </label>
                <input type="checkbox" id="export-file">
                <div class="export__file-options" style="color:#11121a;">
                    <label>Export As &nbsp; &#10140;</label>
                    <label for="export-file" id="toPDF">PDF <img src="../../icons/pdf.png" alt=""></label>
                    <label for="export-file" id="toCSV">CSV <img src="../../icons/csv.png" alt=""></label>
                </div>
            </div>
        </section>
        <section class="table__body">
            <table>
                <thead>
                    <tr>
                        <th> ID <span class="icon-arrow">&UpArrow;</span></th>
                        <th> OFFICE <span class="icon-arrow">&UpArrow;</span></th>
                        <th> STATUS <span class="icon-arrow">&UpArrow;</span></th>
                        <th> TIME <span class="icon-arrow">&UpArrow;</span></th>
                        <th> DATE <span class="icon-arrow">&UpArrow;</span></th>
                    </tr>
                </thead>
                <tbody id="logs-tbody">
                    <!-- Logs will be loaded dynamically here -->
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2rem;">
                            <p>Loading logs...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
        <div id="pagination-container" style="display: none; padding: 1rem; text-align: center;">
            <div id="pagination-controls" style="display: flex; justify-content: center; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <button id="prev-page-btn" class="pagination-btn" style="
                    background-color: #6b7280;
                    color: white;
                    border: none;
                    padding: 0.6rem 1.2rem;
                    border-radius: 0.5rem;
                    font-weight: 600;
                    cursor: pointer;
                    font-size: 0.9rem;
                    transition: background-color 0.2s ease;
                " onmouseover="this.style.backgroundColor='#4b5563'" onmouseout="this.style.backgroundColor='#6b7280'">Previous</button>
                <div id="page-numbers" style="display: flex; gap: 0.3rem; flex-wrap: wrap; justify-content: center;"></div>
                <button id="next-page-btn" class="pagination-btn" style="
                    background-color: #6b7280;
                    color: white;
                    border: none;
                    padding: 0.6rem 1.2rem;
                    border-radius: 0.5rem;
                    font-weight: 600;
                    cursor: pointer;
                    font-size: 0.9rem;
                    transition: background-color 0.2s ease;
                " onmouseover="this.style.backgroundColor='#4b5563'" onmouseout="this.style.backgroundColor='#6b7280'">Next</button>
            </div>
            <div id="page-info" style="margin-top: 0.5rem; color: var(--secondary-color); font-size: 0.85rem;"></div>
        </div>
    </main>
    
</body>

</html>
<style>

@media print {
 .table, .table__body {
  overflow: visible;
  height: auto !important;
  width: auto !important;
 }
}

/*@page {
    size: landscape;
    margin: 0; 
}*/

body {
    min-height: 100vh;
    background-color: var(--base-clr);
    display: flex;
    justify-content: center;
    align-items: center;
    
}

main.table {
    width: 78vw;
    height: 90vh;
    border-right: 1px solid var(--line-clr);

    backdrop-filter: blur(7px);
    box-shadow: 0 .4rem .8rem #0005;
    border-radius: .8rem;

    overflow: hidden;
}

.table__header {
    width: 98%;
    height: 10%;
    background-color: #fff4;
    padding: .8rem 1rem;

    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table__header .input-group {
    width: 35%;
    height: 100%;
    background-color: #fff5;
    padding: 0 .8rem;
    border-radius: 2rem;

    display: flex;
    justify-content: center;
    align-items: center;

    transition: .2s;
}

.table__header .input-group:hover {
    width: 45%;
    background-color: #fff8;
    box-shadow: 0 .1rem .4rem #0002;
}

.table__header .input-group img {
    width: 1.2rem;
    height: 1.2rem;
}

.table__header .input-group input {
    width: 100%;
    padding: 0 .5rem 0 .3rem;
    background-color: transparent;
    border: none;
    outline: none;
}

.table__body {
    width: 95%;
    max-height: calc(89% - 1.6rem);
    background-color: #fffb;

    margin: .8rem auto;
    border-radius: .6rem;

    overflow: auto;
    overflow: overlay;
    color: #11121a;
}


.table__body::-webkit-scrollbar{
    width: 0.5rem;
    height: 0.5rem;
}

.table__body::-webkit-scrollbar-thumb{
    border-radius: .5rem;
    background-color: #0004;
    visibility: hidden;
}

.table__body:hover::-webkit-scrollbar-thumb{ 
    visibility: visible;
}


table {
    width: 100%;
}

td img {
    width: 36px;
    height: 36px;
    margin-right: .5rem;
    border-radius: 50%;

    vertical-align: middle;
}

table, th, td {
    border-collapse: collapse;
    padding: 1rem;
    text-align: left;
}

thead th {
    position: sticky;
    top: 0;
    left: 0;
    background-color: #4f46e5;
    cursor: pointer;
    text-transform: capitalize;
}

tbody tr:nth-child(even) {
    background-color: #0000000b;
}

tbody tr {
    --delay: .1s;
    transition: .5s ease-in-out var(--delay), background-color 0s;
}

tbody tr.hide {
    opacity: 0;
    transform: translateX(100%);
}

tbody tr:hover {
    background-color: #fff6 !important;
}

tbody tr td,
tbody tr td p,
tbody tr td img {
    transition: .2s ease-in-out;
}

tbody tr.hide td,
tbody tr.hide td p {
    padding: 0;
    font: 0 / 0 sans-serif;
    transition: .2s ease-in-out .5s;
}

tbody tr.hide td img {
    width: 0;
    height: 0;
    transition: .2s ease-in-out .5s;
}

@media (max-width: 1000px) {
    td:not(:first-of-type) {
        min-width: 12.1rem;
    }
}
@media screen and (max-width: 768px) {
    main.table {
        width: 95vw;
        height: 95vh;
        margin: 0.5rem;
    }
    
    .table__header {
        flex-direction: column;
        height: auto;
        padding: 0.8rem;
        gap: 0.8rem;
    }
    
    .table__header h1 {
        font-size: 1.2rem;
        margin: 0;
    }
    
    .table__header .input-group {
        width: 100%;
        order: 2;
    }
    
    .table__header .export__file {
        order: 3;
        align-self: flex-end;
    }
    
    .table__body {
        width: 100%;
        margin: 0.5rem 0;
        max-height: calc(95vh - 150px);
        font-size: 0.85rem;
    }
    
    table, th, td {
        padding: 0.5rem 0.3rem;
        font-size: 0.8rem;
    }
    
    thead th {
        font-size: 0.75rem;
        padding: 0.5rem 0.3rem;
    }
    
    td img {
        width: 28px;
        height: 28px;
        margin-right: 0.3rem;
    }
    
    thead th span.icon-arrow {
        width: 0.9rem;
        height: 0.9rem;
        font-size: 0.7rem;
        margin-left: 0.2rem;
    }
    
    #pagination-container {
        padding: 0.8rem;
    }
    
    #pagination-controls {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .pagination-btn {
        width: 100%;
        padding: 0.7rem;
        font-size: 0.9rem;
    }
    
    #page-numbers {
        width: 100%;
        justify-content: center;
        gap: 0.3rem;
    }
    
    .page-number-btn {
        flex: 1;
        max-width: 3rem;
        min-width: 2.5rem;
        height: 2.5rem;
        font-size: 0.85rem;
    }
    
    #page-info {
        font-size: 0.8rem;
        margin-top: 0.5rem;
    }
    
    table {
        min-width: 100%;
        display: table;
    }
    
    table, th, td {
        white-space: nowrap;
    }
}

thead th span.icon-arrow {
    display: inline-block;
    width: 1.3rem;
    height: 1.3rem;
    border-radius: 50%;
    border: 1.4px solid transparent;
    
    text-align: center;
    font-size: 1rem;
    
    margin-left: .5rem;
    transition: .2s ease-in-out;
}

thead th:hover span.icon-arrow{
    border: 1.4px solid #6c00bd;
}

thead th:hover {
    color: #6c00bd;
}

thead th.active span.icon-arrow{
    background-color: #6c00bd;
    color: #fff;
}

thead th.asc span.icon-arrow{
    transform: rotate(180deg);
}

thead th.active,tbody td.active {
    color: #6c00bd;
}

.export__file {
    position: relative;
}

.export__file .export__file-btn {
    display: inline-block;
    width: 2rem;
    height: 2rem;
    background: #fff6 url(../../icons/export.png) center / 80% no-repeat;
    border-radius: 30%;
    transition: .2s ease-in-out;
}

.export__file .export__file-btn:hover { 
    background-color: #fff;
    transform: scale(1.15);
    cursor: pointer;
}

.export__file input {
    display: none;
}

.export__file .export__file-options {
    position: absolute;
    right: 0;
    
    width: 12rem;
    border-radius: .5rem;
    overflow: hidden;
    text-align: center;

    opacity: 0;
    transform: scale(.8);
    transform-origin: top right;
    
    box-shadow: 0 .2rem .5rem #0004;
    
    transition: .2s;
}

.export__file input:checked + .export__file-options {
    opacity: 1;
    transform: scale(1);
    z-index: 100;
}

.export__file .export__file-options label{
    display: block;
    width: 100%;
    padding: .6rem 0;
    background-color: #f2f2f2;
    
    display: flex;
    justify-content: space-around;
    align-items: center;

    transition: .2s ease-in-out;
}

.export__file .export__file-options label:first-of-type{
    padding: 1rem 0;
    background-color: #4f46e5 !important;
}

.export__file .export__file-options label:hover{
    transform: scale(1.05);
    background-color: #fff;
    cursor: pointer;
}

.export__file .export__file-options img{
    width: 2rem;
    height: auto;
}

/* Pagination Container Styles - Desktop */
#pagination-container {
    padding: 1rem;
    text-align: center;
}

#pagination-controls {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.pagination-btn {
    transition: background-color 0.2s ease;
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed !important;
}

.pagination-btn:disabled:hover {
    background-color: #6b7280 !important;
}

.page-number-btn {
    transition: background-color 0.2s ease;
}

#page-numbers {
    display: flex;
    gap: 0.3rem;
    flex-wrap: wrap;
    justify-content: center;
}

#page-info {
    margin-top: 0.5rem;
    color: var(--secondary-color);
    font-size: 0.85rem;
}
</style>

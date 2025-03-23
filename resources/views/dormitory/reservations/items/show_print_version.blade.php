<!DOCTYPE html>
<html>
    <head>
        <title>{{$item->name}}</title>
        <style>

.row {
  display: flex;
}
.row::after {
  content: "";
  display: table;
  clear: both;
}
.timetable-block {
    box-sizing: border-box;
    border: solid 1px black;
}

.table_docs {
    height: 610px;
}

@media print {
  * { margin: 0 !important; padding: 0 !important; }
  html, body {
    /*changing width to 100% causes huge overflow and wrap*/
    height:100%; 
    overflow: hidden;
    background: #FFF;
  }

  .template { width: auto; left:0; top:0; }
  .date,.navbuttons {display: none;}
  strong {
    font-size: 1.7em;
  }

}

.s1 {
  width: 8.3333333333%;
}
.s11 {
  width: 91.6666666666%;
}
.s12 {
  width: 100%;
}
.col {
    float: left;
}

html, body {
    /* This makes the gray background color appear in print, too. */
    print-color-adjust: exact;
    padding: 3px 10px;
}
.red, .orange {
    background-color: #bbbbbb!important;
    color: black!important;
}
        </style>
    </head>
    <body>
        <h1>Terem órarend: {{$item->name}}</h1>
        @livewire('timetable', [
            'items' => [$item],
            'days' => 5,
            'firstHour' => 6,
            'lastHour' => 22,
            'isPrintVersion' => true
        ])
    </body>
</html>

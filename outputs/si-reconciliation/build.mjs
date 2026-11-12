import fs from 'node:fs/promises';
import {createRequire} from 'node:module';
import {pathToFileURL} from 'node:url';
const runtimeRequire=createRequire('C:/Program Files/WindowsApps/OpenAI.CodexPrimaryRuntime.v26-1110-778-0_26.1110.778.0_x64__3k8sg7r9htsxt/dependencies/node/package.json');
const {Workbook,SpreadsheetFile}=await import(pathToFileURL(runtimeRequire.resolve('@oai/artifact-tool')).href);
const dir=new URL('.',import.meta.url).pathname.replace(/^\/([A-Za-z]:)/,'$1');
const d=JSON.parse(await fs.readFile(new URL('analysis.json',import.meta.url),'utf8'));
const wb=Workbook.create();
const money='#,##0.00;[Red](#,##0.00);0.00';
const date=v=>v?(Date.parse(v+'T00:00:00Z')-Date.UTC(1899,11,30))/86400000:null;
function sheet(name,title,context,headers,rows,widths,datecols=[],moneycols=[]){
 const s=wb.worksheets.add(name);s.showGridLines=false;
 const last=rows.length+5,n=headers.length;
 s.getRangeByIndexes(0,0,last,n).format.font={name:'Arial',size:10,color:'#222C3A'};
 s.getRangeByIndexes(0,0,last,n).format.verticalAlignment='center';
 s.getRange('A2').values=[[title]];s.getRange('A2').format.font={size:15,bold:true};
 s.getRange('A3').values=[[context]];s.getRange('A3').format.font={italic:true,color:'#566171'};
 s.getRangeByIndexes(4,0,1,n).values=[headers];
 const cooked=rows.map(row=>row.map((v,i)=>datecols.includes(i)?date(v):v));
 if(rows.length)s.getRangeByIndexes(5,0,rows.length,n).values=cooked;
 for(let i=0;i<n;i++)if(!datecols.includes(i)&&!moneycols.includes(i)&&rows.some(r=>typeof r[i]==='string'&&/^0\d+$/.test(r[i])))s.getRangeByIndexes(5,i,rows.length,1).setNumberFormat('@');
 s.getRangeByIndexes(4,0,1,n).format={fill:'#233A54',font:{name:'Arial',size:10,bold:true,color:'#FFFFFF'},rowHeight:38,wrapText:true,horizontalAlignment:'center'};
 s.getRangeByIndexes(5,0,Math.max(rows.length,1),n).format.rowHeight=32;
 for(let i=0;i<n;i++)s.getRangeByIndexes(4,i,last-4,1).format.columnWidth=widths[i]||20;
 for(const i of datecols)s.getRangeByIndexes(5,i,Math.max(rows.length,1),1).setNumberFormat('yyyy-mm-dd');
 for(const i of moneycols)s.getRangeByIndexes(5,i,Math.max(rows.length,1),1).setNumberFormat(money);
 if(rows.length)s.tables.add(s.getRangeByIndexes(4,0,rows.length+1,n),true,name.replace(/\s/g,'')+'Table');
 s.freezePanes.freezeRows(5);s.freezePanes.freezeColumns(Math.min(3,n));
 return s;
}
const summary=wb.worksheets.add('Summary');summary.showGridLines=false;summary.tabColor='#233A54';
const rheads=['Source row','Source reference','Normalized key','Customer in upload','Entry date','Beginning AR','2026 AR','Comparison amount','EWT','Collections','Cancelled','Retention','Source balance 06/30/26','ERP candidates','Result','ERP references','Review findings','Reference type'];
const review=sheet('Each reference','Invoice existence review','316 source rows checked against 633 active ERP invoices. See Candidate details for each matching ERP record.',rheads,d.review.map(r=>[...r,r[2].split(' ')[0]]),[12,26,18,57,15,19,19,21,18,20,18,18,23,15,23,44,100,16],[4],[5,6,7,8,9,10,11,12]);
review.getRange('Q6:Q321').format.wrapText=true;review.getRange('Q6:Q321').format.rowHeight=60;
review.getRange('O6:O321').conditionalFormats.add('containsText',{text:'Missing',format:{fill:'#FCE8E6',font:{color:'#9B2020',bold:true}}});
review.getRange('O6:O321').conditionalFormats.add('containsText',{text:'Combined',format:{fill:'#FFF1CF',font:{color:'#805800'}}});
sheet('Discrepancies','Discrepancy register','One row per finding and ERP candidate. Findings can overlap. Deltas are ERP less upload. Different reporting dates require reconciliation.', ['Source row','Source reference','Normalized key','Finding','ERP export row','ERP invoice reference','Upload customer','ERP customer','Upload entry date','ERP invoice date','Upload comparison amount','ERP invoice amount','Amount delta','Upload balance 06/30/26','ERP amount less paid','Balance delta','Suggested action'],d.issues,[12,26,18,50,15,32,57,57,16,16,22,22,22,23,23,23,100],[8,9],[10,11,12,13,14,15]);
sheet('Candidate details','ERP candidates for each source row','Combined or suffixed invoice amounts must be allocated before treating their differences as errors. Do not sum repeated ERP candidates.', ['Source row','Source reference','Normalized key','ERP export row','ERP reference','ERP customer','Upload entry date','ERP invoice date','Upload customer','Upload comparison amount','ERP amount','Amount delta','Upload balance 06/30/26','ERP amount less paid','Balance delta','ERP status','ERP SO / DR source','Findings'],d.candidates,[12,26,18,15,30,57,16,16,57,23,23,23,23,23,23,16,50,100],[6,7],[9,10,11,12,13,14]);
sheet('Upload source','Original AR upload values','Source: AR UPLOAD PER SI-2.xlsx, Summary per SI. Includes control totals at source rows 318–320. Blank cells preserved.',d.sourceHeaders,d.source,[12,16,24,27,59,22,22,20,23,22,25,26],[1],[5,6,7,8,9,10,11]);
sheet('ERP source','Active ERP export','Source: https://erp.agenturecorp.com/sales-invoices. Exported 2026-11-11 through signed-in Chrome. Deleted invoices excluded.',d.erpHeaders,d.erp.map(r=>{let t=[...r];const p=t[5].match(/(\w+) (\d+), (\d+)/);t[5]=`${p[3]}-${String(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'].indexOf(p[1])+1).padStart(2,'0')}-${p[2].padStart(2,'0')}`;return t;}),[14,20,30,59,52,17,23,23,16,16],[5],[6,7]);
summary.getRange('A1:F38').format.font={name:'Arial',size:10,color:'#222C3A'};
summary.getRange('A2').values=[['Sales invoice reconciliation']];summary.getRange('A2').format.font={size:15,bold:true};
summary.getRange('A3').values=[['Checked 11 November 2026. Upload balances are labeled 30 June 2026.']];
summary.getRange('A5:B5').values=[['Existence result','Source rows']];
const statuses=['Missing','Combined reference','Multiple candidates','Review differences','Matched'];
for(let i=0;i<statuses.length;i++){summary.getRange(`A${6+i}`).values=[[statuses[i]]];summary.getRange(`B${6+i}`).formulas=[[`=COUNTIFS('Each reference'!O6:O321,A${6+i})`]];}
summary.getRange('A11').values=[['Total source references']];summary.getRange('B11').formulas=[['=SUM(B6:B10)']];
summary.getRange('D5:F5').values=[['Active ERP control','Export sum','Page total']];
summary.getRange('D6:D8').values=[['Invoiced PHP'],['Paid PHP'],['Amount less paid PHP']];
summary.getRange('E6:E8').formulas=[["=SUM('ERP source'!G6:G638)"],["=SUM('ERP source'!H6:H638)"],['=E6-E7']];
summary.getRange('F6:F8').values=[[385614259.09],[38887003.42],[346727255.67]];
summary.getRange('E6:F8').setNumberFormat(money);
summary.getRange('A14:B14').values=[['Missing reference type','Source rows']];
summary.getRange('A15:A16').values=[['SI'],['PI']];
summary.getRange('B15').formulas=[['=COUNTIFS(\'Each reference\'!O6:O321,"Missing",\'Each reference\'!R6:R321,"SI")']];
summary.getRange('B16').formulas=[['=COUNTIFS(\'Each reference\'!O6:O321,"Missing",\'Each reference\'!R6:R321,"PI")']];
const notes=[
 ['Scope','Every populated source reference was checked against the full active ERP export. Missing means absent from that register. Deleted invoices were not checked.'],
 ['Reference matching','SI prefixes and leading zeros are normalized. PI remains separate. For conversion references, the final SI is used. Comma-separated ERP numbers are checked individually.'],
 ['Combined records','40 source rows appear in ERP references containing multiple invoice numbers. Their full ERP amount is shown for evidence and is not an allocation to each SI.'],
 ['Multiple candidates','15 source rows have more than one ERP candidate, including 11 already classified as combined references. Multiple candidates are review items, not confirmed duplicates.'],
 ['Date comparison','Upload Entry Date is compared with ERP Invoice Date. A difference may reflect posting timing rather than a wrong invoice date.'],
 ['Amount comparison','Comparison amount = beginning AR + 2026 AR. Opening balances are remaining AR, so original invoice values are needed before judging those amounts.'],
 ['Balance comparison','ERP Amount less Paid is an indicative current balance. Upload balance is dated 06/30/26. EWT, retention, cancellation and later collections can explain differences.'],
 ['Customer comparison','Case, spaces and punctuation are ignored. Remaining name differences require review and do not by themselves prove a different customer.'],
 ['Source totals','Detail balance totals PHP 137,445,669.31. Source row 318 shows PHP 137,445,669.30, row 319 PHP 137,445,938.01 and row 320 a PHP -268.70 adjustment. Validate these controls.'],
 ['Next action','Review missing references and deleted invoices before importing. Validate combined, split and repeated references before correcting amounts or deleting records.']
];
summary.getRange('A19:B19').values=[['Interpretation','Method and limitations']];
summary.getRange('A20:B29').values=notes;summary.getRange('B20:B29').format.wrapText=true;summary.getRange('A20:B29').format.rowHeight=64;summary.getRange('A20:A29').format.font={bold:true};
for(const range of ['A5:B5','D5:F5','A14:B14','A19:B19'])summary.getRange(range).format={fill:'#233A54',font:{color:'#FFFFFF',bold:true},rowHeight:30};
summary.getRange('A5:A29').format.columnWidth=29;summary.getRange('B5:B29').format.columnWidth=99;
summary.getRange('C5:C29').format.columnWidth=3;summary.getRange('D5:D29').format.columnWidth=29;summary.getRange('E5:F29').format.columnWidth=24;
summary.getRange('B6:B16').setNumberFormat('#,##0');summary.getRange('A11:B11').format.font={bold:true};
summary.getRange('B6:B16').format.horizontalAlignment='left';
wb.recalculate();
console.log((await wb.inspect({kind:'table',range:'Summary!A5:B16',include:'values,formulas',tableMaxRows:12,tableMaxCols:2,maxChars:4000})).ndjson);
console.log((await wb.inspect({kind:'match',searchTerm:'#REF!|#DIV/0!|#VALUE!|#NAME\\?|#NUM!|#SPILL!',options:{useRegex:true,maxResults:20},maxChars:1500})).ndjson);
for(const [name,range] of [['Summary','A2:B29'],['Each reference','A5:E10'],['Discrepancies','A5:F10'],['Candidate details','A5:E10'],['Upload source','A5:E10'],['ERP source','A5:D10']]){
 const blob=await wb.render({sheetName:name,range,scale:1,format:'png'});await fs.writeFile(new URL(name.replaceAll(' ','_')+'.png',import.meta.url),new Uint8Array(await blob.arrayBuffer()));
}
await (await SpreadsheetFile.exportXlsx(wb)).save(new URL('SI_Discrepancy_Report_2026-11-11.xlsx',import.meta.url).pathname.replace(/^\/([A-Za-z]:)/,'$1'));
console.log('Export complete');

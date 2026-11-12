import fs from 'node:fs/promises';
import {createRequire} from 'node:module';
import {pathToFileURL,fileURLToPath} from 'node:url';
const requireRuntime=createRequire('C:/Program Files/WindowsApps/OpenAI.CodexPrimaryRuntime.v26-1110-778-0_26.1110.778.0_x64__3k8sg7r9htsxt/dependencies/node/package.json');
const {FileBlob,SpreadsheetFile}=await import(pathToFileURL(requireRuntime.resolve('@oai/artifact-tool')).href);
const {review}=JSON.parse(await fs.readFile(new URL('analysis.json',import.meta.url),'utf8'));
const workbook=await SpreadsheetFile.importXlsx(await FileBlob.load('C:/Users/RUSHPOINT IT/Downloads/AR UPLOAD PER SI-2.xlsx'));
const sheet=workbook.worksheets.getItem('Summary per SI');
const source=sheet.getRange('C2:C317').values;
const marks=Array.from({length:316},()=>[null]);
for(const [row,ref,,,,,,,,,,,,count] of review){
 if(String(source[row-2][0]).trim()!==ref.trim())throw Error('Source reference changed at row '+row);
 marks[row-2]=[count?'Encoded':'Not encoded'];
}
sheet.getRange('L1').values=[['ERP status']];
sheet.getRange('L2:L317').values=marks;
sheet.getRange('L1:L317').format.columnWidth=17;
sheet.getRange('L1').format.font={bold:true};
workbook.recalculate();
const preview=await workbook.render({sheetName:sheet.name,range:'K1:L9',scale:1,format:'png'});
await fs.writeFile(new URL('marked-preview.png',import.meta.url),new Uint8Array(await preview.arrayBuffer()));
await (await SpreadsheetFile.exportXlsx(workbook)).save(fileURLToPath(new URL('AR UPLOAD PER SI-2 - ERP Status.xlsx',import.meta.url)));

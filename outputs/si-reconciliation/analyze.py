import openpyxl,json,re,datetime,collections,pathlib
out=pathlib.Path(__file__).parent
srcpath=r'C:\Users\RUSHPOINT IT\Downloads\AR UPLOAD PER SI-2.xlsx'
erppath=r'C:\Users\RUSHPOINT IT\Downloads\sales-invoices-ar-2026-11-11.xlsx'
src=list(openpyxl.load_workbook(srcpath,read_only=True,data_only=True).active.values)
erp=list(openpyxl.load_workbook(erppath,read_only=True,data_only=True).active.values)
def clean(v):
 if isinstance(v,(datetime.datetime,datetime.date)):return v.strftime('%Y-%m-%d')
 return v
def key(v):
 s=str(v or '').strip().upper()
 if '--' in s or '->' in s:
  parts=re.split(r'--|->',s); tail=parts[-1].strip()
  if tail.isdigit():tail=('SI ' if 'SI' in s else 'PI ')+tail
  return key(tail)
 si=re.findall(r'\bSI\s*[-#:]?\s*0*(\d+)',s)
 if si:return 'SI '+str(int(si[-1]))
 pi=re.findall(r'\bPI\s*[-#:]?\s*0*(\d+)',s)
 if pi:return 'PI '+str(int(pi[-1]))
 if re.fullmatch(r'\d+(?:\.0)?',s):return 'SI '+str(int(float(s)))
 return s
def name(v):return re.sub(r'[^A-Z0-9]','',str(v or '').upper())
def num(v):return float(v) if isinstance(v,(int,float)) else 0
idx=collections.defaultdict(list)
grouped=set();split=set()
for i,r in enumerate(erp[1:],2):
 text=str(r[0]).strip()
 if ',' in text and re.fullmatch(r'[\d\s,]+',text):
  grouped.add(i)
  for token in text.split(','):idx[key(token)].append((i,r))
 else:
  idx[key(r[0])].append((i,r))
  if re.search(r'\d[a-z]$',text,re.I):split.add(i)
review=[];issues=[]; candidates=[]; used=set()
for i,r in enumerate(src[1:],2):
 if not r[2]:continue
 k=key(r[2]); matches=idx[k]; flags=[]
 if not matches:flags.append('Missing from active ERP')
 if len(matches)>1:flags.append('Multiple ERP candidates')
 if any(j in grouped for j,e in matches):flags.append('Included in combined ERP invoice reference')
 if any(j in split for j,e in matches):flags.append('Split/suffixed ERP invoice reference')
 if sum(key(x[2])==k for x in src[1:] if x[2])>1:flags.append('Repeated normalized reference in source')
 beginning=num(r[4]); ar=num(r[5]); basis=beginning+ar
 if beginning:flags.append('Beginning balance requires original invoice validation')
 if num(r[8]):flags.append('Source has cancellation')
 matched=[]
 for j,e in matches:
  used.add(j); cf=[]
  ed=datetime.datetime.strptime(str(e[3]),'%b %d, %Y').strftime('%Y-%m-%d') if e[3] else None
  if name(r[3])!=name(e[1]):cf.append('Customer name differs')
  if clean(r[0])!=ed:cf.append('Date differs')
  diff=round(num(e[4])-basis,2)
  if abs(diff)>.01:cf.append('Combined/split amount requires allocation' if j in grouped or j in split else 'Amount differs' if not beginning else 'Amount differs from opening AR basis')
  baldiff=round(num(e[4])-num(e[5])-num(r[10]),2)
  if abs(baldiff)>.01:cf.append('Balance timing/reconciliation difference')
  flags+=cf
  matched.append([e[0],e[1],ed,num(e[4]),num(e[5]),diff,baldiff])
  candidates.append([i,str(r[2]),k,j,str(e[0]),e[1],clean(r[0]),ed,r[3],basis,num(e[4]),diff,num(r[10]),num(e[4])-num(e[5]),baldiff,e[6],e[2],'; '.join(cf)])
  for f in cf:issues.append([i,str(r[2]),k,f,j,str(e[0]),str(r[3]),str(e[1]),clean(r[0]),ed,basis,num(e[4]),diff,num(r[10]),num(e[4])-num(e[5]),baldiff,'Review source and ERP record. Balance/collections are different reporting dates.' if f.startswith('Balance') else 'Validate source against original invoice before correcting ERP.'])
 flags=list(dict.fromkeys(flags))
 for f in flags:
  if f in ('Missing from active ERP','Multiple ERP candidates','Beginning balance requires original invoice validation','Source has cancellation','Included in combined ERP invoice reference','Split/suffixed ERP invoice reference','Repeated normalized reference in source'):
   action={'Missing from active ERP':'Check deleted invoices and original document before any import.','Multiple ERP candidates':'Review all candidate records. Do not add their balances or assume a duplicate is confirmed.','Beginning balance requires original invoice validation':'Opening AR is a remaining balance and may differ from original invoice gross amount.','Source has cancellation':'Validate cancellation and its effective date against ERP.','Included in combined ERP invoice reference':'SI is referenced in a combined ERP record. Validate allocated amounts against each underlying invoice.','Split/suffixed ERP invoice reference':'Check suffixed ERP records and allocate amounts to the source invoice.','Repeated normalized reference in source':'Check conversion history and repeated reference before aggregating source rows.'}[f]
   issues.append([i,str(r[2]),k,f,None,'; '.join(str(e[0]) for j,e in matches),str(r[3]),None,clean(r[0]),None,basis,None,None,num(r[10]),None,None,action])
 status='Missing' if not matches else 'Combined reference' if any(j in grouped for j,e in matches) else 'Multiple candidates' if len(matches)>1 else 'Review differences' if flags else 'Matched'
 review.append([i,str(r[2]),k,str(r[3]),clean(r[0]),beginning,ar,basis,num(r[6]),num(r[7]),num(r[8]),num(r[9]),num(r[10]),len(matches),status,'; '.join(str(e[0]) for j,e in matches),'; '.join(flags)])
source_records=[[i]+[clean(v) for v in r[:11]] for i,r in enumerate(src[1:],2) if any(v is not None for v in r[:11])]
erp_records=[[i,key(r[0])]+list(r) for i,r in enumerate(erp[1:],2)]
counts=collections.Counter(r[14] for r in review)
issuecounts=collections.Counter(r[3] for r in issues)
data={'review':review,'issues':issues,'candidates':candidates,'source':source_records,'erp':erp_records,'counts':dict(counts),'issuecounts':dict(issuecounts),'sourceHeaders':['Source row']+list(src[0][:11]),'erpHeaders':['Export row','Normalized key']+list(erp[0]),'erp_total':sum(num(r[4]) for r in erp[1:]),'erp_paid':sum(num(r[5]) for r in erp[1:]),'unused_erp_count':len(erp)-1-len(used)}
(out/'analysis.json').write_text(json.dumps(data,ensure_ascii=False,default=clean),encoding='utf-8')
print(json.dumps({k:data[k] for k in ('counts','issuecounts','erp_total','erp_paid','unused_erp_count')},indent=2))
print('missing',[(r[1],r[3],r[7]) for r in review if r[14]=='Missing'])
print('source non-invoice rows',[(r[0],r[1:5]) for r in source_records if not r[3]])

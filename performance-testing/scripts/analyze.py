from pathlib import Path
import json,csv,datetime,statistics,re
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
P=Path(__file__).resolve().parents[1]
def timestamp(s):
    s=re.sub(r'\.(\d+)',lambda m:'.'+m.group(1)[:6].ljust(6,'0'),s.replace('Z','+00:00'))
    return datetime.datetime.fromisoformat(s).timestamp()
def pct(a,p):
    a=sorted(a)
    if not a:return None
    x=(len(a)-1)*p;i=int(x);return a[i]+(a[min(i+1,len(a)-1)]-a[i])*(x-i)
def points(path):
    out=[]
    for line in path.open(encoding='utf-8-sig'):
        d=json.loads(line)
        if d['type']=='Point':out.append(d)
    return out
def stats(ps,start,end,scenario=None):
    rows=[x for x in ps if x['metric']=='http_req_duration' and x['data']['tags'].get('name')=='analytics' and (scenario is None or x['data']['tags'].get('scenario')==scenario)]
    rows=[x for x in rows if start<=timestamp(x['data']['time'])<end]
    a=[x['data']['value'] for x in rows]
    return {'n':len(a),'avg':statistics.mean(a) if a else None,'median':pct(a,.5),'p90':pct(a,.9),'p95':pct(a,.95),'p99':pct(a,.99),'rps':len(a)/(end-start),'http_error':sum(x['data']['tags'].get('status')!='200' for x in rows)/len(a) if a else None}
rows=[];details={}
for phase in ['before','after']:
    for mode,cid in [('load',1),('load',2),('load',3),('stress',3),('soak',3)]:
        stem=f'{mode}-{cid}';summary=json.loads((P/'results'/phase/(stem+'.json')).read_text())
        ps=points(P/'results'/phase/(stem+'.ndjson'))
        # End of setup is the start of the scenario schedule.
        times=[timestamp(x['data']['time']) for x in ps if x['metric']=='data_sent' and x['data']['tags'].get('group')=='::setup']
        origin=min(times) if times else min(timestamp(x['data']['time']) for x in ps if x['metric']=='analytics_ms')
        m=summary['metrics'];v=m['analytics_ms']['values'];e=m['analytics_error']['values']['rate']
        row={'phase':phase,'test':stem,**v,'error':e,'requests':m['http_reqs']['values']['count'],'duration_ms':summary['state']['testRunDurationMs']}
        calls=[x for x in ps if x['metric']=='http_req_duration' and x['data']['tags'].get('name')=='analytics']
        row['http_error']=sum(x['data']['tags'].get('status')!='200' for x in calls)/len(calls)
        row['failed_checks']=m['checks']['values']['fails']
        log=(P/'results'/phase/(stem+'.txt')).read_text(encoding='utf-8')
        interrupted=re.findall(r'(\d+) interrupted iterations',log)
        row['interrupted_iterations']=int(interrupted[-1]) if interrupted else 0
        row['max_vus']={'load':20,'stress':100,'soak':5}[mode]
        row['dataset_students']={1:50,2:250,3:1000}[cid]
        row['rps']=sum(x['metric']=='analytics_ms' for x in ps)/(row['duration_ms']/1000)
        rows.append(row)
        detail={'origin':origin,'all':row}
        if mode=='stress':
            detail['stages']={str(vu):stats(ps,origin+i*40,origin+i*40+30,'vu'+str(vu)) for i,vu in enumerate([10,20,50,100])}
            detail['stages_with_grace']={str(vu):stats(ps,origin+i*40,origin+i*40+40,'vu'+str(vu)) for i,vu in enumerate([10,20,50,100])}
        if mode=='load':detail['steady']=stats(ps,origin+5,origin+35)
        if mode=='soak':detail['thirds']=[stats(ps,origin+i*60,origin+(i+1)*60) for i in range(3)]
        details[phase+'-'+stem]=detail
(P/'reports/comparison.json').write_text(json.dumps({'rows':rows,'details':details},indent=2),encoding='utf-8')
with (P/'reports/comparison.csv').open('w',newline='',encoding='utf-8-sig') as f:
    w=csv.DictWriter(f,fieldnames=list(rows[0]));w.writeheader();w.writerows(rows)
plt.rcParams.update({'font.size':10})
for metric,title in [('p(95)','p95 odozvy (ms)'),('rps','Dokončené požiadavky analytiky / s')]:
    fig,ax=plt.subplots(figsize=(8,3.4));names=['load-1','load-2','load-3','stress-3','soak-3']
    for phase,offset,color in [('before',-.18,'#8b3848'),('after',.18,'#176c83')]:
        vals=[next(r[metric] for r in rows if r['phase']==phase and r['test']==name) for name in names]
        ax.bar([i+offset for i in range(5)],vals,width=.36,label=phase.upper(),color=color)
    ax.set_xticks(range(5),['Load S','Load M','Load L','Stress L','Soak L']);ax.set_ylabel(title);ax.legend();ax.grid(axis='y',alpha=.2);fig.tight_layout();fig.savefig(P/'reports'/('p95.png' if metric=='p(95)' else 'throughput.png'),dpi=180);plt.close(fig)
fig,ax=plt.subplots(figsize=(8,3.4))
for phase in ['before','after']:
    ds=details[phase+'-stress-3']['stages_with_grace'];ax.plot([10,20,50,100],[ds[str(v)]['p95'] for v in [10,20,50,100]],marker='o',label=phase.upper())
ax.set_xlabel('Virtuálni používatelia');ax.set_ylabel('p95 odpovedí vrátane grace period (ms)');ax.legend();ax.grid(alpha=.2);fig.tight_layout();fig.savefig(P/'reports/stress.png',dpi=180)
print(json.dumps(rows,indent=2))

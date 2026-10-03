import http from 'k6/http';
import {check,sleep,fail} from 'k6';
import {Trend,Rate} from 'k6/metrics';
const latency=new Trend('analytics_ms',true), errors=new Rate('analytics_error');
const base=__ENV.BASE_URL||'http://127.0.0.1:8765';
const cid=Number(__ENV.COURSE||3), n={1:50,2:250,3:1000}[cid];
const mode=__ENV.MODE||'load';
export const options={summaryTrendStats:['avg','med','p(90)','p(95)','p(99)','max'],
 scenarios:mode==='stress'?Object.fromEntries([10,20,50,100].map((v,i)=>['vu'+v,{executor:'constant-vus',vus:v,duration:'30s',startTime:(i*40)+'s',gracefulStop:'10s',tags:{phase:String(v)}}])):
 mode==='soak'?{soak:{executor:'constant-vus',vus:5,duration:(__ENV.SOAK_SECONDS||180)+'s'}}:
 {load:{executor:'ramping-vus',startVUs:0,stages:[{duration:'5s',target:20},{duration:'30s',target:20},{duration:'5s',target:0}],gracefulRampDown:'10s'}},
 thresholds:{analytics_error:['rate<0.01']},setupTimeout:'120s'};
export function setup(){
 let r=http.get(base+'/login');const version=JSON.parse(r.html().find('[data-page]').attr('data-page')).version;let token=r.html().find('meta[name="csrf-token"]').attr('content');
 if(!token) fail('Missing CSRF token');
 r=http.post(base+'/login',{email:'teacher@performance.test',password:'Perf-local-2026!',_token:token},{redirects:0});
 if(r.status!==302) fail('Login failed '+r.status);
 const cookies=http.cookieJar().cookiesForURL(base);
 let warm=http.get(base+'/teacher/courses/'+cid+'/manage',{headers:{'X-Inertia':'true','X-Inertia-Version':version,'Accept':'text/html, application/xhtml+xml'}});
 if(warm.status!==200) fail('Warmup failed '+warm.status);
 return {cookies,version};
}
export default function(data){
 const version=data.version;
 for(const [key,values] of Object.entries(data.cookies)) http.cookieJar().set(base,key,values[0]);
 let r=http.get(base+'/teacher/courses/'+cid+'/manage',{headers:{'X-Inertia':'true','X-Inertia-Version':version,'Accept':'text/html, application/xhtml+xml'},timeout:'30s',tags:{name:'analytics'}});
 let valid=false;try{const a=r.json('props.analytics');valid=r.status===200&&a.enrolled_count===n&&a.test_attempts_count===n*5&&a.students_attempted_count===n;}catch(e){}
 check(r,{'HTTP 200 and correct analytics':()=>valid});latency.add(r.timings.duration);errors.add(!valid);sleep(1);
}
export function handleSummary(data){return {[__ENV.SUMMARY||'summary.json']:JSON.stringify(data,null,2)};}

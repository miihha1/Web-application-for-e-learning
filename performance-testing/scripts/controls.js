import http from 'k6/http';
import {check,fail,sleep} from 'k6';
import {Trend,Rate} from 'k6/metrics';
const listTime=new Trend('course_list_ms',true),submitTime=new Trend('submit_ms',true),bad=new Rate('control_errors');
const base=__ENV.BASE_URL||'http://127.0.0.1:8765';
export const options={noCookiesReset:true,vus:1,iterations:20,summaryTrendStats:['avg','med','p(90)','p(95)','p(99)'],thresholds:{control_errors:['rate<0.01']}};
let token,version;
export default function(){
 if(__ITER===0){
  let r=http.get(base+'/login');token=r.html().find('meta[name="csrf-token"]').attr('content');
  r=http.post(base+'/login',{email:'student-1-0@performance.test',password:'Perf-local-2026!',_token:token},{redirects:0});
  if(r.status!==302)fail('Login failed');
 }
 let r=http.get(base+'/courses/1/test');
 token=r.html().find('meta[name="csrf-token"]').attr('content');
 const page=JSON.parse(r.html().find('[data-page]').attr('data-page'));version=page.version;
 const headers={'X-Inertia':'true','X-Inertia-Version':version,'Accept':'text/html, application/xhtml+xml'};
 r=http.get(base+'/courses',{headers,tags:{name:'course-list'}});listTime.add(r.timings.duration);
 let ok=r.status===200;try{ok=ok&&r.json('props.courses').length===3;}catch(e){ok=false;}
 const answers={};for(const q of page.props.test.questions)answers[q.id]=[q.options[0].id];
 r=http.post(base+'/courses/1/test',JSON.stringify({answers}),{headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token,'Accept':'text/html'},redirects:0,tags:{name:'test-submit'}});submitTime.add(r.timings.duration);ok=ok&&r.status===302;
 r=http.get(base+'/courses/1/test/results',{headers});
 try{ok=ok&&r.status===200&&r.json('props.results.latest.percent')==100&&r.json('props.results.latest.attempt')===6+__ITER;}catch(e){ok=false;}
 check(r,{'list, submission and saved result correct':()=>ok});bad.add(!ok);sleep(1);
}
export function handleSummary(data){delete data.setup_data;return {[__ENV.SUMMARY]:JSON.stringify(data,null,2)};}

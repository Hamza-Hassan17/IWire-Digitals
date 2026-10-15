(function(){
const pal=[[205,265],[330,20],[160,210],[28,350],[275,190],[45,15],[190,240],[300,250],[120,170],[10,40],[220,300],[350,280]];
function art(i,style){
  const [a,b]=pal[i%pal.length]; const s=style??(i%5);
  const base=`linear-gradient(145deg, hsl(${a} 65% 22%), hsl(${b} 70% 7%))`;
  if(s===0) return `radial-gradient(circle at 28% 30%, hsl(${a} 90% 62% / .95) 0, transparent 46%), radial-gradient(circle at 78% 78%, hsl(${b} 90% 55% / .85) 0, transparent 52%), ${base}`;
  if(s===1) return `repeating-radial-gradient(circle at 70% 35%, hsl(${a} 85% 60% / .55) 0 2px, transparent 3px 14px), radial-gradient(circle at 70% 35%, hsl(${b} 90% 55%) 0, transparent 60%), ${base}`;
  if(s===2) return `linear-gradient(180deg, transparent 55%, hsl(${b} 80% 8% / .9)), repeating-linear-gradient(115deg, hsl(${a} 80% 55% / .35) 0 10px, transparent 10px 26px), ${base}`;
  if(s===3) return `conic-gradient(from 210deg at 35% 65%, hsl(${a} 90% 60%), hsl(${b} 85% 45%), hsl(${a} 70% 20%), hsl(${a} 90% 60%))`;
  return `radial-gradient(ellipse 60% 40% at 50% 100%, hsl(${a} 95% 60% / .9) 0, transparent 70%), radial-gradient(circle at 50% 38%, hsl(${b} 80% 70% / .9) 0 14%, transparent 15%), linear-gradient(180deg, hsl(${b} 60% 10%), hsl(${a} 70% 25%))`;
}
const ST={
  Pending:{bg:'rgba(245,176,65,.14)',fg:'#F5C46B',dot:'#F5B041'},
  Live:{bg:'rgba(52,199,123,.14)',fg:'#5FD99A',dot:'#34C77B'},
  Rejected:{bg:'rgba(240,82,82,.14)',fg:'#F38A8A',dot:'#F05252'},
  Unpublished:{bg:'rgba(148,163,184,.14)',fg:'#A7B4C6',dot:'#8796AA'},
  Flagged:{bg:'rgba(249,128,52,.15)',fg:'#FBA26A',dot:'#F98034'},
  Open:{bg:'rgba(249,128,52,.15)',fg:'#FBA26A',dot:'#F98034'},
  Resolved:{bg:'rgba(52,199,123,.14)',fg:'#5FD99A',dot:'#34C77B'},
  Dismissed:{bg:'rgba(148,163,184,.14)',fg:'#A7B4C6',dot:'#8796AA'},
  Active:{bg:'rgba(52,199,123,.14)',fg:'#5FD99A',dot:'#34C77B'},
  Suspended:{bg:'rgba(240,82,82,.14)',fg:'#F38A8A',dot:'#F05252'},
  Hidden:{bg:'rgba(148,163,184,.14)',fg:'#A7B4C6',dot:'#8796AA'},
  Reported:{bg:'rgba(249,128,52,.15)',fg:'#FBA26A',dot:'#F98034'},
  Visible:{bg:'rgba(52,199,123,.14)',fg:'#5FD99A',dot:'#34C77B'},
  Sent:{bg:'rgba(10,155,255,.14)',fg:'#6CC4FF',dot:'#0A9BFF'},
  Scheduled:{bg:'rgba(245,176,65,.14)',fg:'#F5C46B',dot:'#F5B041'},
};
const k=n=>n>=1e6?(n/1e6).toFixed(n>=1e7?0:1)+'M':n>=1e3?(n/1e3).toFixed(n>=1e4?0:1)+'K':String(n);
const rnd=s=>{let x=Math.sin(s*9301+49297)*233280;return x-Math.floor(x)};
function wave(n,seed){const o=[];for(let i=0;i<n;i++){const env=Math.sin(Math.PI*i/n)*.55+.45;o.push(Math.round((18+rnd(seed+i)*82)*env));}return o;}
function path(arr,w,h,max,min){max=max??Math.max(...arr);min=min??0;const st=w/(arr.length-1);return arr.map((v,i)=>(i?'L':'M')+(i*st).toFixed(1)+' '+(h-(v-min)/(max-min)*h).toFixed(1)).join(' ');}
function area(arr,w,h,max,min){return path(arr,w,h,max,min)+` L${w} ${h} L0 ${h} Z`;}
function series(n,seed,base,amp,trend){const o=[];for(let i=0;i<n;i++)o.push(Math.round(base+trend*i+Math.sin(i/2.3+seed)*amp+rnd(seed*7+i)*amp*.8));return o;}

const tracks=[
 ['Midnight Transit','Nova Reyes','Electronic','Late Night','3:42',1284000,'Live'],
 ['Gold Teeth Sermon','Kairo Banks','Hip-Hop','Hype','2:58',982400,'Live'],
 ['Soft Static','Lumen Hale','Lo-fi','Focus','2:31',871200,'Live'],
 ['Velvet Hours','Amara Sol','R&B','Late Night','4:05',764900,'Live'],
 ['Paper Planets','The Quiet Fleet','Electronic','Chill','3:19',612300,'Live'],
 ['Concrete Bloom','Dre Okafor','Hip-Hop','Hype','3:07',540800,'Live'],
 ['Rainroom','Mika Tanaka','Lo-fi','Chill','2:44',438100,'Live'],
 ['Afterglow Drive','Juno Vance','R&B','Late Night','3:51',402700,'Unpublished'],
 ['Pulse Theory','Ezra Lune','Electronic','Focus','5:12',356000,'Live'],
 ['Blue Hour Tape','Saint Marlo','Lo-fi','Focus','2:16',298400,'Flagged'],
 ['Crown Season','Kairo Banks','Hip-Hop','Hype','3:33',264900,'Live'],
 ['Silk Signal','Amara Sol','R&B','Chill','3:28',201300,'Rejected'],
].map((r,i)=>({id:'t'+i,type:'Music',title:r[0],creator:r[1],genre:r[2],mood:r[3],dur:r[4],plays:r[5],playsK:k(r[5]),status:r[6],art:art(i),shares:Math.round(r[5]/140),comments:Math.round(r[5]/900),uploaded:['Sep 2','Aug 28','Aug 21','Aug 19','Aug 11','Aug 9','Jul 30','Jul 24','Jul 18','Jul 12','Jul 3','Jun 29'][i]}));
const films=[
 ['Ember of the North','Ilse Varga','Fantasy','1h 52m',842000,'Live',.62],
 ['The Last Lighthouse','Tomas Ruiz','Drama','1h 38m',731500,'Live',.34],
 ['Signal Lost','Priya Desai','Action','2h 04m',688200,'Live',.81],
 ['Salt & Static','Odile Fontaine','Documentary','1h 12m',512300,'Live',.15],
 ['Glass Harbor','Kwame Asante','Drama','1h 46m',466900,'Live',.48],
 ['Hollow Crown','Ilse Varga','Fantasy','2h 11m',401400,'Live',.07],
 ['Night Runners','Leo Marchetti','Action','1h 41m',388000,'Unpublished',.55],
 ['Roots Underground','Nadia Bloom','Documentary','58m',254700,'Live',.9],
 ['Paper Moons','Sora Kim','Drama','1h 29m',198600,'Flagged',.22],
 ['Ironvale','Leo Marchetti','Action','1h 55m',142000,'Live',.4],
].map((r,i)=>({id:'f'+i,type:'Films',title:r[0],creator:r[1],genre:r[2],dur:r[3],plays:r[4],playsK:k(r[4]),status:r[5],progress:Math.round(r[6]*100)+'%',art:art(i+3,[2,4,0,1,3][i%5]),shares:Math.round(r[4]/120),comments:Math.round(r[4]/700),uploaded:['Sep 4','Aug 30','Aug 25','Aug 20','Aug 14','Aug 7','Jul 29','Jul 22','Jul 15','Jul 8'][i]}));
const queue=[
 ['Neon Psalms','Kairo Banks','Music','Hip-Hop','3:24','Oct 12, 09:14',52],
 ['Tidewater','Odile Fontaine','Films','Documentary','1h 04m','Oct 12, 14:40',46],
 ['Low Orbit','Ezra Lune','Music','Electronic','4:48','Oct 12, 21:02',40],
 ['Satin Rooftop','Amara Sol','Music','R&B','3:11','Oct 13, 02:30',34],
 ['The Cartographer','Sora Kim','Films','Fantasy','1h 47m','Oct 13, 06:18',30],
 ['Dust Library','Mika Tanaka','Music','Lo-fi','2:39','Oct 13, 09:51',27],
 ['Afterparty Physics','Nova Reyes','Music','Electronic','3:57','Oct 13, 13:05',23],
 ['Ricochet','Leo Marchetti','Films','Action','1h 36m','Oct 13, 16:44',20],
 ['Sunday Dialect','Dre Okafor','Music','Hip-Hop','3:02','Oct 13, 19:20',17],
 ['Glassworks','Kwame Asante','Films','Drama','1h 22m','Oct 13, 22:12',14],
 ['Cloud Library','Lumen Hale','Music','Lo-fi','2:12','Oct 14, 03:40',9],
 ['Still Water Run','Juno Vance','Music','R&B','3:44','Oct 14, 07:55',4],
].map((r,i)=>({id:'q'+i,title:r[0],creator:r[1],type:r[2],genre:r[3],dur:r[4],submitted:r[5],hrs:r[6],waiting:r[6]>=48?'Waiting '+Math.floor(r[6]/24)+'d '+(r[6]%24)+'h':'Waiting '+r[6]+' hrs',urgent:r[6]>=36,art:art(i+5,r[2]==='Films'?2+(i%3):i%5),icon:r[2]==='Music'?'graphic_eq':'movie',status:'Pending'}));
const people=['Nova Reyes','Kairo Banks','Lumen Hale','Amara Sol','The Quiet Fleet','Dre Okafor','Ilse Varga','Tomas Ruiz','Priya Desai','Odile Fontaine','Kwame Asante','Leo Marchetti'];
const creators=people.map((n,i)=>{const film=i>=6;const up=[24,31,18,15,9,22,6,4,7,5,8,11][i];const appr=[92,88,95,83,100,86,83,75,86,100,88,73][i];return {id:'c'+i,name:n,initials:n.split(' ').map(x=>x[0]).join('').slice(0,2),type:film?'Filmmaker':'Musician',icon:film?'movie':'graphic_eq',uploads:up,plays:k([3.2e6,2.8e6,1.9e6,1.4e6,8.1e5,1.1e6,1.6e6,9.4e5,8.8e5,7.1e5,6.2e5,5.3e5][i]),rate:appr+'%',rateW:appr+'%',status:i===11?'Suspended':'Active',art:art(i+1,0),email:n.toLowerCase().replace(/[^a-z]/g,'.')+'@mail.com',joined:['Mar 2025','Jan 2025','Apr 2025','Feb 2025','Jun 2025','May 2025','Jan 2025','Jul 2025','Mar 2025','Aug 2025','May 2025','Apr 2025'][i],approved:Math.round(up*appr/100),rejected:up-Math.round(up*appr/100)}});
const ln=['Ayesha Khan','Marcus Bell','Lena Fischer','Jordan Pike','Chen Wei','Sofia Marín','Tariq Hale','Grace Obi','Noah Brandt','Isla Moreau','Ravi Menon','Zara Lindqvist','Owen Carter','Mei Ishikawa'];
const listeners=ln.map((n,i)=>({id:'l'+i,name:n,initials:n.split(' ').map(x=>x[0]).join(''),email:n.toLowerCase().split(' ')[0]+'.'+n.toLowerCase().split(' ')[1][0]+'@mail.com',joined:['Oct 2, 2026','Sep 28, 2026','Sep 14, 2026','Sep 3, 2026','Aug 22, 2026','Aug 9, 2026','Jul 30, 2026','Jul 12, 2026','Jun 29, 2026','Jun 3, 2026','May 18, 2026','Apr 30, 2026','Apr 2, 2026','Mar 21, 2026'][i],last:['2 min ago','14 min ago','1 hr ago','3 hrs ago','Today','Yesterday','Yesterday','2 days ago','4 days ago','1 wk ago','2 wks ago','3 wks ago','1 mo ago','2 mo ago'][i],streams:k([4210,3876,3102,2840,2611,2207,1980,1644,1310,980,742,512,301,96][i]),status:i===9||i===13?'Suspended':'Active',hue:art(i+2,0)}));
const comments=[
 ['Ayesha Khan','This bridge at 2:10 is unreal. On repeat all week.','Midnight Transit',0,'Visible','2 min ago'],
 ['Marcus Bell','Check out my channel for free beats 🔥 link in bio','Gold Teeth Sermon',1,'Reported','18 min ago'],
 ['Lena Fischer','The cinematography in the lighthouse scene… chef’s kiss.','The Last Lighthouse',13,'Visible','42 min ago'],
 ['Jordan Pike','Mid. The first one was better honestly.','Signal Lost',14,'Visible','1 hr ago'],
 ['Tariq Hale','[removed slur] this artist is trash','Concrete Bloom',5,'Hidden','2 hrs ago'],
 ['Grace Obi','Perfect focus music for exams, thank you Lumen!','Soft Static',2,'Visible','3 hrs ago'],
 ['Noah Brandt','Spoiler: the captain dies at the end lol','Ember of the North',12,'Reported','5 hrs ago'],
 ['Chen Wei','Need the instrumental version of this asap','Velvet Hours',3,'Visible','6 hrs ago'],
 ['Sofia Marín','Documentary made me cry. Important story.','Salt & Static',15,'Visible','Yesterday'],
 ['Owen Carter','Stolen sample, this is from an old soul record','Blue Hour Tape',9,'Reported','Yesterday'],
].map((r,i)=>({id:'cm'+i,user:r[0],initials:r[0].split(' ').map(x=>x[0]).join(''),text:r[1],item:r[2],art:r[3]<12?tracks[r[3]].art:films[r[3]-12].art,status:r[4],time:r[5],hue:art(i+4,0)}));
const reports=[
 ['Blue Hour Tape','Saint Marlo','Track','Copyright infringement',14,'Oct 14','Open',tracks[9].art],
 ['Paper Moons','Sora Kim','Film','Inappropriate content',9,'Oct 13','Open',films[8].art],
 ['“Check out my channel…”','Marcus Bell','Comment','Spam',6,'Oct 13','Open',art(1,0)],
 ['“Spoiler: the captain…”','Noah Brandt','Comment','Spoilers',4,'Oct 12','Open',art(7,0)],
 ['Night Runners','Leo Marchetti','Film','Misleading metadata',3,'Oct 11','Resolved',films[6].art],
 ['Afterglow Drive','Juno Vance','Track','Explicit not labelled',5,'Oct 9','Resolved',tracks[7].art],
 ['“[removed]”','Tariq Hale','Comment','Hate speech',11,'Oct 8','Resolved',art(5,0)],
 ['Rainroom','Mika Tanaka','Track','Low audio quality',2,'Oct 6','Dismissed',tracks[6].art],
].map((r,i)=>({id:'r'+i,item:r[0],by:r[1],kind:r[2],reason:r[3],count:r[4],date:r[5],status:r[6],art:r[7],icon:r[2]==='Track'?'graphic_eq':r[2]==='Film'?'movie':'chat_bubble'}));
const activity=[
 ['check_circle','#5FD99A','Approved','“Pulse Theory” by Ezra Lune','Today, 10:42','Music'],
 ['cancel','#F38A8A','Rejected','“Silk Signal” by Amara Sol — Metadata issues','Today, 10:31','Music'],
 ['dashboard_customize','#6CC4FF','Curation published','Films home · Hero carousel (5 slides)','Today, 09:58','Films'],
 ['campaign','#6CC4FF','Notification sent','“New drop from Nova Reyes” → Music users','Today, 09:15','Music'],
 ['visibility_off','#A7B4C6','Unpublished','“Night Runners” by Leo Marchetti','Yesterday, 18:20','Films'],
 ['block','#F38A8A','Suspended user','Isla Moreau (listener)','Yesterday, 16:02','All'],
 ['check_circle','#5FD99A','Approved','“Roots Underground” by Nadia Bloom','Yesterday, 14:47','Films'],
 ['flag','#FBA26A','Report resolved','“Afterglow Drive” — marked explicit','Yesterday, 11:30','Music'],
 ['check_circle','#5FD99A','Approved','“Concrete Bloom” by Dre Okafor','Oct 12, 17:12','Music'],
 ['dashboard_customize','#6CC4FF','Curation published','Music home · Find Your Mood','Oct 12, 15:40','Music'],
 ['cancel','#F38A8A','Rejected','“Sideways” by Juno Vance — Quality','Oct 12, 13:08','Music'],
 ['block','#F38A8A','Suspended user','Leo Marchetti (creator)','Oct 11, 10:22','Films'],
].map((r,i)=>({id:'a'+i,icon:r[0],color:r[1],action:r[2],target:r[3],time:r[4],ctx:r[5]}));
const sent=[
 ['New drop from Nova Reyes','“Midnight Transit” is out now. Tap to listen.','Music users','Today, 09:15','184K','12.4%','Sent'],
 ['Weekend premiere','Ember of the North streams free all weekend.','Films users','Oct 18, 18:00','—','—','Scheduled'],
 ['Your Late Night mix is ready','Fresh R&B and electronic for after dark.','Music users','Oct 11, 21:00','172K','9.8%','Sent'],
 ['Creators: new upload limits','You can now upload films up to 8 GB.','Creators','Oct 8, 12:00','1.2K','41.0%','Sent'],
 ['Trending this week','Signal Lost is #1 on iWire Films.','All users','Oct 5, 17:30','326K','8.1%','Sent'],
].map((r,i)=>({id:'n'+i,title:r[0],body:r[1],aud:r[2],time:r[3],reach:r[4],ctr:r[5],status:r[6]}));
const genres={
 music:[['Hip-Hop','#0A9BFF',214],['R&B','#7A6CFF',168],['Electronic','#18C6D8',241],['Lo-fi','#4F86C6',132],['Afrobeats','#2CB58A',96],['Soul','#B783FF',71]],
 films:[['Drama','#4F86C6',58],['Action','#0A9BFF',44],['Documentary','#2CB58A',31],['Fantasy','#7A6CFF',27],['Thriller','#18C6D8',19]],
 moods:[['Chill','#18C6D8',312],['Hype','#0A9BFF',276],['Focus','#4F86C6',198],['Late Night','#7A6CFF',241]],
};
for(const g in genres) genres[g]=genres[g].map((r,i)=>({id:g+i,name:r[0],color:r[1],count:r[2],art:art(i+(g==='films'?4:g==='moods'?8:0),i%5)}));
window.IW={art,ST,k,wave,path,area,series,tracks,films,queue,creators,listeners,comments,reports,activity,sent,genres,rnd};
})();

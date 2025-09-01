/* MentorConnect Optimized JavaScript - Consolidated and Minified */
class MentorConnectApp{
constructor(){
this.theme=localStorage.getItem('theme')||'light';
this.sidebarOpen=window.innerWidth>768;
this.cache=new Map();
this.debounceTimers=new Map();
this.init();
}

init(){
requestAnimationFrame(()=>{
this.initializeTheme();
this.initializeSidebar();
this.initializeNotifications();
this.bindEvents();
this.initializePerformanceMonitoring();
});
}

initializeTheme(){
const themeToggle=document.querySelector('.theme-toggle');
if(!themeToggle)return;
const savedTheme=localStorage.getItem('theme')||'light';
document.documentElement.setAttribute('data-theme',savedTheme);
this.updateThemeIcon(savedTheme);
themeToggle.addEventListener('click',()=>{
const currentTheme=document.documentElement.getAttribute('data-theme');
const newTheme=currentTheme==='dark'?'light':'dark';
document.documentElement.setAttribute('data-theme',newTheme);
localStorage.setItem('theme',newTheme);
this.updateThemeIcon(newTheme);
this.saveThemePreference();
});
}

updateThemeIcon(theme){
const themeToggle=document.querySelector('.theme-toggle');
if(themeToggle){
const icon=themeToggle.querySelector('i');
icon.className=theme==='dark'?'fas fa-sun':'fas fa-moon';
}
}

async saveThemePreference(){
try{
await fetch('/api/user-preferences.php',{
method:'POST',
headers:{'Content-Type':'application/json'},
body:JSON.stringify({theme:this.theme})
});
}catch(error){
console.error('Failed to save theme preference:',error);
}
}

initializeSidebar(){
const sidebar=document.querySelector('.sidebar');
const mainContent=document.querySelector('.main-content');
if(window.innerWidth<=768){
sidebar?.classList.add('collapsed');
mainContent?.classList.add('expanded');
this.sidebarOpen=false;
}
}

toggleSidebar(){
const sidebar=document.querySelector('.sidebar');
const mainContent=document.querySelector('.main-content');
if(this.sidebarOpen){
sidebar?.classList.add('collapsed');
mainContent?.classList.add('expanded');
}else{
sidebar?.classList.remove('collapsed');
mainContent?.classList.remove('expanded');
}
this.sidebarOpen=!this.sidebarOpen;
}

async performSearch(query){
if(query.length<2){
this.clearSearchResults();
return;
}
const cacheKey=`search_${query}`;
if(this.cache.has(cacheKey)){
this.displaySearchResults(this.cache.get(cacheKey));
return;
}
try{
const controller=new AbortController();
const timeoutId=setTimeout(()=>controller.abort(),5000);
const response=await fetch(`/api/search.php?q=${encodeURIComponent(query)}`,{
signal:controller.signal,
headers:{'X-Requested-With':'XMLHttpRequest'}
});
clearTimeout(timeoutId);
if(!response.ok)throw new Error(`HTTP ${response.status}`);
const results=await response.json();
this.cache.set(cacheKey,results);
setTimeout(()=>this.cache.delete(cacheKey),300000);
this.displaySearchResults(results);
}catch(error){
if(error.name!=='AbortError'){
console.error('Search failed:',error);
this.showToast('Search failed. Please try again.','error');
}
}
}

displaySearchResults(results){
console.log('Search results:',results);
}

initializeNotifications(){
this.loadNotifications();
this.startNotificationPolling();
}

startNotificationPolling(interval=30000){
const poll=async()=>{
try{
await this.loadNotifications();
setTimeout(poll,interval);
}catch(error){
const backoffInterval=Math.min(interval*2,300000);
console.warn(`Notification polling failed, retrying in ${backoffInterval/1000}s`);
setTimeout(poll,backoffInterval);
}
};
setTimeout(poll,interval);
}

async loadNotifications(){
try{
const controller=new AbortController();
const timeoutId=setTimeout(()=>controller.abort(),10000);
const response=await fetch('/api/notifications.php?action=count',{
signal:controller.signal,
headers:{'X-Requested-With':'XMLHttpRequest'}
});
clearTimeout(timeoutId);
if(!response.ok)throw new Error(`HTTP ${response.status}`);
const data=await response.json();
if(data.success){
this.updateNotificationBadge(data.unread_count);
}
}catch(error){
if(error.name!=='AbortError'){
console.error('Failed to load notifications:',error);
}
}
}

updateNotificationBadge(count){
const badge=document.querySelector('.notification-badge');
if(badge){
if(count>0){
badge.textContent=count>99?'99+':count;
badge.style.display='block';
}else{
badge.style.display='none';
}
}
}

bindEvents(){
document.addEventListener('click',(e)=>{
if(e.target.closest('.menu-toggle')){
this.toggleSidebar();
}
});

window.addEventListener('resize',()=>{
if(window.innerWidth>768&&!this.sidebarOpen){
this.toggleSidebar();
}else if(window.innerWidth<=768&&this.sidebarOpen){
this.toggleSidebar();
}
});

this.enhanceForms();
}

enhanceForms(){
document.querySelectorAll('textarea').forEach(textarea=>{
const resizeTextarea=this.throttle(()=>{
textarea.style.height='auto';
textarea.style.height=textarea.scrollHeight+'px';
},100);
textarea.addEventListener('input',resizeTextarea);
});

document.querySelectorAll('input[type="file"]').forEach(input=>{
input.addEventListener('change',(e)=>{
const file=e.target.files[0];
if(file&&file.type.startsWith('image/')){
if(file.size>10*1024*1024){
this.showToast('File size must be less than 10MB','error');
input.value='';
return;
}
const reader=new FileReader();
reader.onload=(e)=>{
const preview=document.querySelector('.file-preview');
if(preview){
const img=document.createElement('img');
img.src=e.target.result;
img.alt='Preview';
img.style.cssText='max-width:200px;border-radius:8px;object-fit:cover;';
img.loading='lazy';
preview.innerHTML='';
preview.appendChild(img);
}
};
reader.readAsDataURL(file);
}
});
});
}

showToast(message,type='info'){
const toast=document.createElement('div');
toast.className=`toast toast-${type}`;
toast.innerHTML=`
<div class="toast-content">
<i class="fas fa-${this.getToastIcon(type)}"></i>
<span>${message}</span>
</div>
<button class="toast-close" onclick="this.parentElement.remove()">
<i class="fas fa-times"></i>
</button>
`;
document.body.appendChild(toast);
setTimeout(()=>{
toast.classList.add('show');
},100);
setTimeout(()=>{
if(toast.parentElement){
toast.remove();
}
},4000);
}

getToastIcon(type){
const icons={
success:'check-circle',
error:'exclamation-circle',
warning:'exclamation-triangle',
info:'info-circle'
};
return icons[type]||'info-circle';
}

async makeRequest(url,options={}){
const defaultOptions={
headers:{
'Content-Type':'application/json',
'X-Requested-With':'XMLHttpRequest'
}
};
const mergedOptions={...defaultOptions,...options};
try{
const response=await fetch(url,mergedOptions);
if(!response.ok){
throw new Error(`HTTP error! status: ${response.status}`);
}
const contentType=response.headers.get('content-type');
if(contentType&&contentType.includes('application/json')){
return await response.json();
}else{
return await response.text();
}
}catch(error){
console.error('Request failed:',error);
this.showToast('Request failed. Please try again.','error');
throw error;
}
}

validateForm(form){
const requiredFields=form.querySelectorAll('[required]');
let isValid=true;
requiredFields.forEach(field=>{
if(!field.value.trim()){
this.showFieldError(field,'This field is required');
isValid=false;
}else{
this.clearFieldError(field);
}
});
return isValid;
}

showFieldError(field,message){
const formGroup=field.closest('.form-group');
if(!formGroup)return;
formGroup.classList.add('error');
let errorElement=formGroup.querySelector('.error-message');
if(!errorElement){
errorElement=document.createElement('div');
errorElement.className='error-message';
field.parentNode.insertAdjacentElement('afterend',errorElement);
}
errorElement.innerHTML=`<i class="fas fa-exclamation-circle"></i> ${message}`;
}

clearFieldError(field){
const formGroup=field.closest('.form-group');
if(!formGroup)return;
formGroup.classList.remove('error');
const errorElement=formGroup.querySelector('.error-message');
if(errorElement){
errorElement.remove();
}
}

debounce(func,wait){
return(...args)=>{
const key=func.toString();
clearTimeout(this.debounceTimers.get(key));
this.debounceTimers.set(key,setTimeout(()=>func.apply(this,args),wait));
};
}

throttle(func,limit){
let inThrottle;
return(...args)=>{
if(!inThrottle){
func.apply(this,args);
inThrottle=true;
setTimeout(()=>inThrottle=false,limit);
}
};
}

initializePerformanceMonitoring(){
if('PerformanceObserver' in window){
const observer=new PerformanceObserver((list)=>{
list.getEntries().forEach((entry)=>{
if(entry.entryType==='navigation'){
console.log('Page Load Time:',entry.loadEventEnd-entry.loadEventStart);
}
});
});
observer.observe({entryTypes:['navigation']});
}
}

clearSearchResults(){
const resultsContainer=document.querySelector('.search-results');
if(resultsContainer){
resultsContainer.innerHTML='';
}
}

destroy(){
this.debounceTimers.forEach(timer=>clearTimeout(timer));
this.debounceTimers.clear();
this.cache.clear();
}
}

// Auto-initialize when DOM is ready
if(document.readyState==='loading'){
document.addEventListener('DOMContentLoaded',initializeApp);
}else{
initializeApp();
}

function initializeApp(){
try{
window.mentorConnectApp=new MentorConnectApp();
document.body.classList.add('loaded');
preloadCriticalResources();
}catch(error){
console.error('Failed to initialize app:',error);
initializeFallback();
}
}

function preloadCriticalResources(){
const criticalEndpoints=[
'/api/notifications.php?action=count',
'/api/user-preferences.php'
];
criticalEndpoints.forEach(url=>{
const link=document.createElement('link');
link.rel='prefetch';
link.href=url;
document.head.appendChild(link);
});
}

function initializeFallback(){
const themeToggle=document.querySelector('.theme-toggle');
if(themeToggle){
themeToggle.addEventListener('click',()=>{
const currentTheme=document.documentElement.getAttribute('data-theme');
const newTheme=currentTheme==='dark'?'light':'dark';
document.documentElement.setAttribute('data-theme',newTheme);
localStorage.setItem('theme',newTheme);
});
}
}

// Global theme reset function
window.resetThemeToLight=function(){
if(window.mentorConnectApp){
document.documentElement.setAttribute('data-theme','light');
localStorage.setItem('theme','light');
const themeIcon=document.querySelector('.theme-toggle i');
if(themeIcon){
themeIcon.className='fas fa-moon';
}
}
console.log('Theme reset to light mode');
};

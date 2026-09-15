#!/usr/bin/env node
/** First online setup: resolve npm's stable dist-tags, check peers, pin exact versions. */
import { readFileSync, writeFileSync, existsSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
process.chdir(root);
const [major,minor]=process.versions.node.split('.').map(Number);
if(major<22||(major===22&&minor<12))throw new Error('Use Node.js 22.12+; Node 24 is the project baseline.');
const npm=process.platform==='win32'?'npm.cmd':'npm';
const run=(args,options={})=>execFileSync(npm,args,{cwd:root,encoding:'utf8',shell:process.platform==='win32',...options});
const pkg=JSON.parse(readFileSync('package.json','utf8'));
if(existsSync('package-lock.json')&&!process.argv.includes('--refresh')){
 console.log('A lockfile already exists. Installing its exact dependency graph with npm ci.');
 run(['ci'],{stdio:'inherit'});
}else{
 const resolved={};
 console.log('Resolving current stable npm versions. No canary, next, RC, --force or legacy peer bypass.');
 for(const name of [...Object.keys(pkg.dependencies),...Object.keys(pkg.devDependencies)]){
  const version=JSON.parse(run(['view',`${name}@latest`,'version','--json'],{timeout:45000}));
  if(typeof version!=='string'||!/^\d+\.\d+\.\d+$/.test(version))throw new Error(`A stable version could not be resolved for ${name}. Received ${JSON.stringify(version)}.`);
  resolved[name]=version;console.log(`${name} ${version}`);
 }
 if(resolved.react!==resolved['react-dom'])throw new Error('React and React DOM stable versions differ. Review the release/peer requirements before proceeding.');
 const next={...pkg,dependencies:Object.fromEntries(Object.keys(pkg.dependencies).map(n=>[n,resolved[n]])),devDependencies:Object.fromEntries(Object.keys(pkg.devDependencies).map(n=>[n,resolved[n]]))};
 // Keep the pre-resolution manifest so a failed install never hides its provenance.
 writeFileSync('package.bootstrap-backup.json',JSON.stringify(pkg,null,2)+'\n');
 writeFileSync('package.json',JSON.stringify(next,null,2)+'\n');
 console.log('Installing resolved versions with strict engines and peer checks.');
 run(['install','--save-exact','--strict-peer-deps','--engine-strict'],{stdio:'inherit'});
}
execFileSync(process.execPath,['scripts/record-versions.mjs'],{cwd:root,stdio:'inherit'});
console.log('\nDependencies are installed. Now run npm run check, then npm run dev.');
console.log('Commit package.json and package-lock.json after review. Use npm ci on subsequent machines.');

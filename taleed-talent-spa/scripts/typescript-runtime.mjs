import { createRequire } from 'node:module';
import { execFileSync } from 'node:child_process';
import path from 'node:path';
const require=createRequire(import.meta.url);
export function loadTypeScript(){
 try{return require('typescript');}catch{}
 try{const npm=process.platform==='win32'?'npm.cmd':'npm';const root=execFileSync(npm,['root','-g'],{encoding:'utf8',shell:process.platform==='win32'}).trim();return require(path.join(root,'typescript'));}
 catch{throw new Error('TypeScript is required for these source checks. Install project dependencies first.');}
}

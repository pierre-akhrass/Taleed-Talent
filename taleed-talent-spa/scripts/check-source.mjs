/** Syntax/relative-import check ONLY. Not a substitute for typechecking/build/browser tests. */
import fs from 'node:fs';import path from 'node:path';import {fileURLToPath} from 'node:url';
import {loadTypeScript} from './typescript-runtime.mjs';
const ts=loadTypeScript(),root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const files=[];const walk=d=>{for(const entry of fs.readdirSync(d,{withFileTypes:true})){const p=path.join(d,entry.name);if(entry.isDirectory())walk(p);else if(/\.tsx?$/.test(entry.name))files.push(p);}};walk(path.join(root,'src'));
const errors=[];for(const file of files){const code=fs.readFileSync(file,'utf8');const result=ts.transpileModule(code,{fileName:file,reportDiagnostics:true,compilerOptions:{target:ts.ScriptTarget.ES2022,module:ts.ModuleKind.ESNext,jsx:ts.JsxEmit.ReactJSX,isolatedModules:true}});for(const d of result.diagnostics??[])if(d.category===ts.DiagnosticCategory.Error)errors.push(`${path.relative(root,file)}: ${ts.flattenDiagnosticMessageText(d.messageText,' ')}`);
 const source=ts.createSourceFile(file,code,ts.ScriptTarget.Latest,true,file.endsWith('tsx')?ts.ScriptKind.TSX:ts.ScriptKind.TS);
 const visit=n=>{if((ts.isImportDeclaration(n)||ts.isExportDeclaration(n))&&n.moduleSpecifier&&ts.isStringLiteral(n.moduleSpecifier)){const ref=n.moduleSpecifier.text;if(ref.startsWith('.')){const base=path.resolve(path.dirname(file),ref);if(![base,base+'.ts',base+'.tsx',base+'.css',path.join(base,'index.ts')].some(p=>fs.existsSync(p)))errors.push(`${path.relative(root,file)}: missing relative module ${ref}`);}}ts.forEachChild(n,visit);};visit(source);
}
const report={checkedAt:new Date().toISOString(),typescript:ts.version,files:files.length,errors,status:errors.length?'FAIL':'PASS',scope:'TS/TSX syntax and static relative imports only; no dependency-aware typecheck or browser execution.'};
fs.mkdirSync(path.join(root,'docs'),{recursive:true});fs.writeFileSync(path.join(root,'docs/source-check-results.json'),JSON.stringify(report,null,2)+'\n');console.log(JSON.stringify(report,null,2));if(errors.length)process.exitCode=1;

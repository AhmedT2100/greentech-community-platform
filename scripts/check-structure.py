#!/usr/bin/env python3
"""Check static resource/include references; dynamic PHP/JS paths need runtime testing."""
from pathlib import Path
import json,re,sys
from urllib.parse import unquote,urlsplit
ROOT=Path(__file__).resolve().parents[1]
PUBLIC=ROOT/'public'
missing=[]; checked=0
for path in sorted(PUBLIC.rglob('*')):
    if not path.is_file() or path.suffix not in {'.php','.html','.css'} or any(p in path.parts for p in ['vendor','uploads']):continue
    text=path.read_text(errors='replace')
    text=re.sub(r'<!--.*?-->', '', text, flags=re.S)
    text=re.sub(r'<\?(?:php|=).*?\?>', '', text, flags=re.S)
    refs=[]
    for m in re.finditer(r'''(?<![\w-])(?:src|href|action)\s*=\s*(["'])(.*?)\1''',text,re.I):
        refs.append(('resource',m.group(2),path.parent))
    for m in re.finditer(r'''^[ \t]*(?:require|include)(?:_once)?\s*(?:\(\s*)?(?:__DIR__\s*\.\s*)?(["'])([^"']+)\1''',path.read_text(errors='replace'),re.M):
        value=m.group(2)
        if '__DIR__' in m.group(0):value=value.lstrip('/')
        refs.append(('include',value,path.parent))
    if path.suffix=='.css':
        refs.extend(('resource',m.group(1).strip('"\''),path.parent) for m in re.finditer(r'url\(([^)]+)\)',text))
    for kind,value,base in refs:
        if not value or any(t in value for t in ['<?','?>','$','{','<','>',"'",'"','+']):continue
        parsed=urlsplit(value)
        if parsed.scheme or parsed.netloc or value.startswith('#'):continue
        value=unquote(parsed.path)
        if not value:continue
        # Bare JavaScript identifiers and data are outside this check's scope.
        target=(PUBLIC/value.lstrip('/')) if value.startswith('/') else base/value
        checked+=1
        if not target.exists():missing.append({'file':str(path.relative_to(ROOT)),'kind':kind,'target':value})
result={'checked':checked,'missing_count':len(missing),'missing':missing}
print(json.dumps(result,indent=2))
# Existing legacy/template references are reported rather than silently hidden.
if '--strict' in sys.argv and missing:sys.exit(1)

if '--check' in sys.argv:
    baseline=json.loads((ROOT/'docs/structural-check.json').read_text())['missing']
    new=[entry for entry in missing if entry not in baseline]
    if new:
        print('New missing references: ' + json.dumps(new), file=sys.stderr)
        sys.exit(1)

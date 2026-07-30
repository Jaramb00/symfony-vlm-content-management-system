# smoke-test.ps1 — brzi API smoke test (pokreni: .\smoke-test.ps1)
# Preduvjet: aplikacija radi (symfony serve / php -S) i worker po potrebi
param(
    [string]$BaseUrl = "http://127.0.0.1:8000"
)

$script:Passed = 0
$script:Failed = 0

# Helper: salje zahtjev curl-om, vraca status code
# Body ide kroz temp datoteku ("-d @file") — PowerShell 5.1 inace
# progura JSON bez navodnika i server dobije smece
function Invoke-Api {
    param($Method, $Path, $Body, $Token, $ContentType = "application/json")
    $args = @("-s", "-o", "$env:TEMP\smoke-body.json", "-w", "%{http_code}", "-X", $Method, "$BaseUrl$Path")
    if ($Body) {
        $bodyFile = "$env:TEMP\smoke-request.json"
        [IO.File]::WriteAllText($bodyFile, $Body)
        $args += @("-H", "Content-Type: $ContentType", "-d", "@$bodyFile")
    }
    if ($Token) { $args += @("-H", "Authorization: Bearer $Token") }
    return (& curl.exe @args)
}
function Get-LastBody {
    Get-Content "$env:TEMP\smoke-body.json" -Raw | ConvertFrom-Json
}

function Assert-Status {
    param($Name, $Expected, $Actual)
    if ("$Actual" -eq "$Expected") {
        Write-Host "[PASS] $Name ($Actual)" -ForegroundColor Green
        $script:Passed++
    } else {
        Write-Host "[FAIL] $Name - ocekivano $Expected, dobiveno $Actual" -ForegroundColor Red
        Get-Content "$env:TEMP\smoke-body.json" -Raw | Write-Host
        $script:Failed++
    }
}

Write-Host "`n=== Smoke test: $BaseUrl ===`n"

# Svjezi korisnik za svaki run (timestamp u emailu = nema sudara s proslim runom)
$email = "smoke$(Get-Date -Format 'yyyyMMddHHmmss')@test.local"
$pass  = "smoketest123"

# --- Registracija ---
$s = Invoke-Api POST "/api/register" "{`"email`":`"$email`",`"password`":`"$pass`"}"
Assert-Status "registracija novog korisnika" 201 $s

$s = Invoke-Api POST "/api/register" "{`"email`":`"$email`",`"password`":`"$pass`"}"
Assert-Status "registracija duplikata -> 409" 409 $s

$s = Invoke-Api POST "/api/register" '{"email":"nije-email","password":"smoketest123"}'
Assert-Status "neispravan email -> 400" 400 $s

$s = Invoke-Api POST "/api/register" '{"email":"kratka@test.local","password":"abc"}'
Assert-Status "prekratka lozinka -> 400" 400 $s

# --- Login ---
$s = Invoke-Api POST "/api/login" "{`"email`":`"$email`",`"password`":`"$pass`"}"
Assert-Status "login" 200 $s
$token = (Get-LastBody).token
if (-not $token) { Write-Host "[FAIL] token nije dobiven, prekid" -ForegroundColor Red; exit 1 }

$s = Invoke-Api POST "/api/login" "{`"email`":`"$email`",`"password`":`"kriva-lozinka`"}"
Assert-Status "login s krivom lozinkom -> 401" 401 $s

# --- Auth guard ---
$s = Invoke-Api GET "/api/content-request"
Assert-Status "lista bez tokena -> 401" 401 $s

# --- ContentRequest ---
$s = Invoke-Api POST "/api/content-request" '{"description":"bez naslova"}' $token
Assert-Status "create bez title -> 400" 400 $s

$s = Invoke-Api POST "/api/content-request" '{tito' $token
Assert-Status "neispravan JSON -> 400 (ne 500!)" 400 $s

$s = Invoke-Api POST "/api/content-request" '{"title":"Smoke test request"}' $token
Assert-Status "create s title -> 201" 201 $s
$requestId = (Get-LastBody).id

$s = Invoke-Api GET "/api/content-request/$requestId" $null $token
Assert-Status "show vlastitog requesta" 200 $s

$s = Invoke-Api GET "/api/content-request/99999999" $null $token
Assert-Status "show nepostojeceg -> 404" 404 $s

$s = Invoke-Api GET "/api/content-request/$requestId/response" $null $token
Assert-Status "AI response prije obrade -> 404" 404 $s

# --- Upload (generira 1x1 PNG u temp) ---
$pngB64 = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=="
$pngPath = "$env:TEMP\smoke-pixel.png"
[IO.File]::WriteAllBytes($pngPath, [Convert]::FromBase64String($pngB64))

$s = & curl.exe -s -o "$env:TEMP\smoke-body.json" -w "%{http_code}" -X POST `
    -H "Authorization: Bearer $token" -F "file=@$pngPath;type=image/png" `
    "$BaseUrl/api/media/upload/$requestId"
Assert-Status "upload slike -> 201" 201 $s

$s = & curl.exe -s -o "$env:TEMP\smoke-body.json" -w "%{http_code}" -X POST `
    -H "Authorization: Bearer $token" `
    "$BaseUrl/api/media/upload/$requestId"
Assert-Status "upload bez datoteke -> 400" 400 $s

# --- Analytics ---
$s = Invoke-Api GET "/api/analytics/latency" $null $token
Assert-Status "analytics latency" 200 $s

# --- Sazetak ---
Write-Host "`n=== Rezultat: $Passed proslo, $Failed palo ===`n" -ForegroundColor $(if ($Failed -eq 0) { "Green" } else { "Red" })
exit $(if ($Failed -eq 0) { 0 } else { 1 })
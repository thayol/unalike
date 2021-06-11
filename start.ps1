#Requires -Version 7.0

$Host.UI.RawUI.WindowTitle = "osu! Unalike IRC worker"
$file = "requests.json"
Write-Host "[Unalike] Service loop started."
while($true)
{
	$content = Get-Content $file
	$decision = $content -eq "{}"
	if ($decision)
	{
	}
	else
	{
		Write-Host "[Unalike] Starting IRC bot..."
		Write-Host "Request: $content"
		Set-Content -Path $file -Value "{}"
		$flags = "unalike.py"
		$flagArray = $flags -split " "
		Start-Process -FilePath python -ArgumentList $flagArray -NoNewWindow -Wait
		Set-Content -Path $file -Value "{}"
		Write-Host "[Unalike] The bot is offline. Initialize it using the request file."
	}
	
	Start-Sleep -Seconds 2
}
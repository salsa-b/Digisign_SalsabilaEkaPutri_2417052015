<?php
/**
 * pages/download_ca.php
 * 02. Download CA ITB Certificate
 */
?>

<div class="max-w-xl mx-auto space-y-6">

    <!-- Page heading -->
    <div>
        <h2 class="text-2xl font-bold text-slate-800">⬇️ Download CA ITB Certificate</h2>
        <p class="text-sm text-slate-500 mt-1">
            Instal sertifikat CA agar dokumen yang ditandatangani terpercaya di perangkat Anda.
        </p>
    </div>

    <!-- Warning banner -->
    <div class="flex gap-3 rounded-xl bg-amber-50 border border-amber-300 p-4 text-amber-800">
        <span class="text-xl shrink-0 mt-0.5">⚠️</span>
        <div class="text-sm leading-relaxed">
            <p class="font-bold mb-1">Peringatan Keamanan</p>
            <p>
                Pastikan hanya download dari <strong>website ini</strong>.
                Jangan install sertifikat CA dari sumber tidak dikenal —
                hal ini dapat membahayakan keamanan perangkat Anda.
            </p>
        </div>
    </div>

    <!-- Download card -->
    <div class="card bg-white border border-slate-200 shadow-sm">
        <div class="card-body p-7 space-y-6">

            <!-- File info -->
            <div class="flex items-center gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200">
                <div class="text-5xl select-none">📜</div>
                <div class="flex-1">
                    <p class="font-semibold text-slate-800 text-sm">
                        InstitutTeknologiBandung.crt
                    </p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        X.509 Certificate &bull; Format: .crt &bull; ~2 KB
                    </p>
                    <p class="text-xs text-blue-600 mt-1 font-medium">
                        SHA-256 Fingerprint: <code class="font-mono">A1:B2:C3:D4:…</code>
                    </p>
                </div>
            </div>

            <!-- Big download button -->
            <a href="#"
               class="btn btn-primary btn-lg w-full gap-3 text-base shadow-md hover:shadow-lg transition-shadow">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2
                             M7 10l5 5 5-5M12 15V3"/>
                </svg>
                Download InstitutTeknologiBandung.crt
            </a>

            <!-- Install guide -->
            <div class="space-y-3">
                <p class="text-xs font-semibold text-slate-600 uppercase tracking-wide">
                    Panduan Instalasi
                </p>
                <div class="space-y-2 text-sm text-slate-600">
                    <?php
                    $guides = [
                        ['🪟','Windows','Double-click file .crt → "Install Certificate" → Local Machine → Trusted Root Certification Authorities → Finish.'],
                        ['🍎','macOS','Buka Keychain Access → File → Import Items → pilih .crt → set Trust ke "Always Trust".'],
                        ['🐧','Linux','<code class="bg-slate-100 px-1 rounded text-xs font-mono">sudo cp InstitutTeknologiBandung.crt /usr/local/share/ca-certificates/ && sudo update-ca-certificates</code>'],
                        ['📱','Android / iOS','Buka file dari aplikasi Files / Settings → Security → Install CA Certificate.'],
                    ];
                    foreach ($guides as [$icon, $os, $desc]): ?>
                    <div class="flex gap-3 p-3 rounded-lg bg-slate-50 border border-slate-100">
                        <span class="text-lg shrink-0"><?= $icon ?></span>
                        <div>
                            <p class="font-semibold text-slate-700 text-xs mb-0.5"><?= $os ?></p>
                            <p class="text-xs text-slate-500 leading-relaxed"><?= $desc ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>

</div>

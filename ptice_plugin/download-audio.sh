#!/bin/bash
# ============================================================
# download-audio.sh
# ПИО Рајац — птице плагин
# Преузима 34 аудио снимка са Wikimedia Commons и
# ажурира путање у свим HTML страницама.
#
# УПОТРЕБА:
#   1. Отпакуј ptice-komplet-final.zip у wp-content/plugins/
#   2. Покрени: cd /путања/до/wp-content/plugins/ptice
#              bash download-audio.sh
#
# Потребно: wget или curl (обоје доступни на cPanel/Linux)
# ============================================================

PLUGIN_DIR="$(cd "$(dirname "$0")" && pwd)"
AUDIO_DIR="$PLUGIN_DIR/assets/audio"
VIEWS_DIR="$PLUGIN_DIR/views"

echo "============================================"
echo " ПИО Рајац — преузимање аудио снимака"
echo " Дестинација: $AUDIO_DIR"
echo "============================================"
echo ""

# Прovjeri да ли постоји wget или curl
if command -v wget &>/dev/null; then
    DOWNLOADER="wget"
elif command -v curl &>/dev/null; then
    DOWNLOADER="curl"
else
    echo "ГРЕШКА: Потребан је wget или curl."
    exit 1
fi

echo "Користим: $DOWNLOADER"
echo ""

# Функција за преузимање
download_file() {
    local url="$1"
    local dest="$2"
    
    if [ -f "$dest" ]; then
        echo "  ПОСТОЈИ: $(basename $dest) — прескачем"
        return 0
    fi
    
    if [ "$DOWNLOADER" = "wget" ]; then
        wget -q --no-check-certificate -O "$dest" "$url"
    else
        curl -s -L --insecure -o "$dest" "$url"
    fi
    
    if [ $? -eq 0 ] && [ -s "$dest" ]; then
        SIZE=$(du -h "$dest" | cut -f1)
        echo "  ✓ $(basename $dest) ($SIZE)"
        return 0
    else
        echo "  ✗ ГРЕШКА: $(basename $dest)"
        rm -f "$dest"
        return 1
    fi
}

# ============================================================
# СПИСАК: slug|url|lokalni_fajl
# ============================================================

echo "Преузимање 34 аудио снимка..."
echo ""

OK=0
FAIL=0

download_file "https://upload.wikimedia.org/wikipedia/commons/6/6e/Aegithalos_caudatus_-_Long-tailed_Tit_XC512590.mp3" "$AUDIO_DIR/aegithalos-caudatus.mp3" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/8/8c/Mystery_mystery_-_Identity_unknown_XC571979.mp3" "$AUDIO_DIR/alauda-arvensis.mp3" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/0/04/Anthus_trivialis_-_Tree_Pipit_XC588228.mp3" "$AUDIO_DIR/anthus-trivialis.mp3" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/f/f4/Carduelis_carduelis_-_European_Goldfinch_XC101463.mp3" "$AUDIO_DIR/carduelis-carduelis.mp3" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/2/26/Gr%C3%BCnfink_Chloris_chloris_0066a.mp3" "$AUDIO_DIR/chloris-chloris.mp3" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/a/a1/Coccothraustes_coccothraustes_-_Hawfinch_XC541308.mp3" "$AUDIO_DIR/coccothraustes-coccothraustes.mp3" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/1/1c/De-Racke.ogg" "$AUDIO_DIR/coracias-garrulus.ogg" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/4/4c/Coturnix_coturnix_-_Common_Quail_XC549215.mp3" "$AUDIO_DIR/coturnix-coturnix.mp3" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/5/53/Delichon_urbicum_contact_call.ogg" "$AUDIO_DIR/delichon-urbicum.ogg" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/b/b2/De-Grauammer.ogg" "$AUDIO_DIR/emberiza-calandra.ogg" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/5/58/Emberiza_hortulana_-_Ortolan_Bunting_XC481324.mp3" "$AUDIO_DIR/emberiza-hortulana.mp3" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/f/f4/Erithacus_rubecula_-_European_Robin_XC124862.ogg" "$AUDIO_DIR/erithacus-rubecula.ogg" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://commons.wikimedia.org/wiki/Special:Redirect/file/Ficedula%20hypoleuca.ogg" "$AUDIO_DIR/ficedula-hypoleuca.ogg" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/9/96/Ficedula_parva_-_Red-breasted_Flycatcher_XC249313.mp3" "$AUDIO_DIR/ficedula-parva.mp3" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/7/77/Hirundo_rustica_-_Barn_Swallow_XC83449.mp3" "$AUDIO_DIR/hirundo-rustica.mp3" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/d/da/De-Heidelerche.ogg" "$AUDIO_DIR/lullula-arborea.ogg" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/3/37/Bijeneter_-_SoundCloud_-_Beeld_en_Geluid.ogg" "$AUDIO_DIR/merops-apiaster.ogg" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/d/dd/Motacilla_cinerea_-_Grey_Wagtail_XC596811.mp3" "$AUDIO_DIR/motacilla-cinerea.mp3" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/7/72/Muscicapa_striata_-_Spotted_Flycatcher_XC342793.mp3" "$AUDIO_DIR/muscicapa-striata.mp3" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/6/6e/Saxicola_rubetra_-_Whinchat_XC479662.mp3" "$AUDIO_DIR/obicna-travarka.mp3" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/4/4b/De-Steinschm%C3%A4tzer.ogg" "$AUDIO_DIR/oenanthe-oenanthe.ogg" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/0/0e/Phasianus_colchicus_-_Common_Pheasant_XC83152.mp3" "$AUDIO_DIR/phasianus-colchicus.mp3" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://commons.wikimedia.org/wiki/Special:Redirect/file/Black%20Redstart%20(Phoenicurus%20ochruros)%20(W1CDR0000253%20BD17).ogg" "$AUDIO_DIR/phoenicurus-ochruros.ogg" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/7/7c/Phoenicurus_phoenicurus_-_Common_Redstart_XC468918.mp3" "$AUDIO_DIR/phoenicurus-phoenicurus.mp3" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/e/e2/Phylloscopus_collybita_-_Common_Chiffchaff_XC501590.mp3" "$AUDIO_DIR/phylloscopus-collybita.mp3" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/7/7a/Phylloscopus_trochilus_-_Willow_Warbler_XC468919.mp3" "$AUDIO_DIR/phylloscopus-trochilus.mp3" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://commons.wikimedia.org/wiki/Special:Redirect/file/European%20Stonechat%20(Saxicola%20rubicola)%20(W1CDR0001536%20BD16).oga" "$AUDIO_DIR/saxicola-rubicola.ogg" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/6/68/Serinus_serinus_-_European_Serin_XC471334.mp3" "$AUDIO_DIR/serinus-serinus.mp3" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/7/7a/Sylvia_atricapilla_-_Eurasian_Blackcap_XC543236.mp3" "$AUDIO_DIR/sylvia-atricapilla.mp3" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/9/91/Sylvia_communis_-_Common_Whitethroat_XC503635.mp3" "$AUDIO_DIR/sylvia-communis.mp3" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/2/22/Turdus_merula_-_Common_Blackbird_XC123548.ogg" "$AUDIO_DIR/turdus-merula.ogg" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/d/d4/Turdus_viscivorus_-_Mistle_Thrush_XC108362.mp3" "$AUDIO_DIR/turdus-viscivorus.mp3" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/a/a1/De-Hupatz.ogg" "$AUDIO_DIR/upupa-epops.ogg" && OK=$((OK+1)) || FAIL=$((FAIL+1))
download_file "https://upload.wikimedia.org/wikipedia/commons/d/dc/Passer_domesticus_-_House_Sparrow_-_XC86749.ogg" "$AUDIO_DIR/vrabac.ogg" && OK=$((OK+1)) || FAIL=$((FAIL+1))

echo ""
echo "============================================"
echo " Преузимање завршено: $OK успешно, $FAIL грешака"
echo "============================================"
echo ""

if [ $FAIL -gt 0 ]; then
    echo "УПОЗОРЕЊЕ: $FAIL фајлова није преузето."
    echo "Страница ће и даље радити — те птице ће учитавати"
    echo "аудио са Wikimedia Commons уместо локално."
    echo ""
fi

# ============================================================
# АЖУРИРАЊЕ HTML ПУТАЊА
# Замена http(s) Wikimedia URL → assets/audio/slug.ext
# ============================================================

echo "Ажурирање путања у HTML страницама..."
echo ""

UPDATED=0

for html_file in "$VIEWS_DIR"/*.html; do
    fname=$(basename "$html_file")
    # Прескочи помоћне странице
    case "$fname" in
        index.html|archive.html|ptice.html|fotografisanje-ptica.html|kucice-za-ptice.html|merlin-ptice-rajac.html)
            continue ;;
    esac
    
    slug="${fname%.html}"
    changed=0
    
    # Провери да ли страница има екстерни аудио
    if grep -q 'upload.wikimedia.org\|commons.wikimedia.org/wiki/Special' "$html_file" 2>/dev/null; then
        
        # Одреди тип фајла (mp3 или ogg)
        if [ -f "$AUDIO_DIR/${slug}.mp3" ]; then
            LOCAL_SRC="audio/${slug}.mp3"
            LOCAL_TYPE="audio/mpeg"
        elif [ -f "$AUDIO_DIR/${slug}.ogg" ]; then
            LOCAL_SRC="audio/${slug}.ogg"
            LOCAL_TYPE="audio/ogg"
        else
            echo "  ПРЕСКАЧЕМ (фајл није преузет): $slug"
            continue
        fi
        
        # Замени <source src="http..." type="..."> → локална путања
        # Користи perl за поуздану замену (доступан на cPanel)
        perl -i -pe '
            s|<source\s+src="https?://[^"]*wikimedia[^"]*"\s+type="[^"]*">|<source src="'"$LOCAL_SRC"'" type="'"$LOCAL_TYPE"'">|g;
            s|<source\s+type="[^"]*"\s+src="https?://[^"]*wikimedia[^"]*">|<source src="'"$LOCAL_SRC"'" type="'"$LOCAL_TYPE"'">|g;
        ' "$html_file"
        
        # Провери да ли је промена уписана
        if ! grep -q 'upload.wikimedia.org\|commons.wikimedia.org/wiki/Special' "$html_file" 2>/dev/null; then
            echo "  ✓ $slug → $LOCAL_SRC"
            UPDATED=$((UPDATED+1))
            changed=1
        fi
    fi
done

echo ""
echo "============================================"
echo " Ажуриране HTML странице: $UPDATED"
echo "============================================"
echo ""
echo "Све готово! Плагин сада служи аудио локално."
echo ""

# Листа свих аудио фајлова у assets/audio/
AUDIO_COUNT=$(ls "$AUDIO_DIR"/*.mp3 "$AUDIO_DIR"/*.ogg 2>/dev/null | wc -l)
echo "Аудио фајлова у assets/audio/: $AUDIO_COUNT"

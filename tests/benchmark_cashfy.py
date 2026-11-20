import pandas as pd
import matplotlib.pyplot as plt

def main():
    # 1. Histórico completo das 15 etapas (5 a 1.000 VUs) - Grafana k6 v2.4.0
    dados = [
        {
            "Etapa": 1, "VUs": 5, "Duracao": "30s", "Iteracoes": 150, "Http_Reqs": 150,
            "Reqs_Status_200": 150, "RPS": 4.866563, "RPS_Efetivo_200": 4.866563,
            "Avg_ms": 24.40, "Avg_Valid_ms": 24.40, "Min_ms": 4.71, "Med_ms": 25.17,
            "Max_ms": 56.76, "p90_ms": 33.64, "p95_ms": 40.97, "p95_Valid_ms": 40.97,
            "Http_Req_Failed_Pct": 0.00, "Http_Req_Failed_Count": 0,
            "Checks_Total": 300, "Checks_Succeeded_Pct": 100.00, "SLA_2s_Violations": 0
        },
        {
            "Etapa": 2, "VUs": 10, "Duracao": "1m", "Iteracoes": 590, "Http_Reqs": 590,
            "Reqs_Status_200": 590, "RPS": 9.720079, "RPS_Efetivo_200": 9.720079,
            "Avg_ms": 23.28, "Avg_Valid_ms": 23.28, "Min_ms": 4.07, "Med_ms": 24.43,
            "Max_ms": 61.90, "p90_ms": 32.67, "p95_ms": 35.67, "p95_Valid_ms": 35.67,
            "Http_Req_Failed_Pct": 0.00, "Http_Req_Failed_Count": 0,
            "Checks_Total": 1180, "Checks_Succeeded_Pct": 100.00, "SLA_2s_Violations": 0
        },
        {
            "Etapa": 3, "VUs": 20, "Duracao": "1m", "Iteracoes": 1179, "Http_Reqs": 1179,
            "Reqs_Status_200": 1179, "RPS": 19.447796, "RPS_Efetivo_200": 19.447796,
            "Avg_ms": 25.38, "Avg_Valid_ms": 25.38, "Min_ms": 4.82, "Med_ms": 25.25,
            "Max_ms": 383.71, "p90_ms": 34.44, "p95_ms": 38.61, "p95_Valid_ms": 38.61,
            "Http_Req_Failed_Pct": 0.00, "Http_Req_Failed_Count": 0,
            "Checks_Total": 2358, "Checks_Succeeded_Pct": 100.00, "SLA_2s_Violations": 0
        },
        {
            "Etapa": 4, "VUs": 30, "Duracao": "2m", "Iteracoes": 3506, "Http_Reqs": 3506,
            "Reqs_Status_200": 3506, "RPS": 29.069907, "RPS_Efetivo_200": 29.069907,
            "Avg_ms": 28.00, "Avg_Valid_ms": 28.00, "Min_ms": 4.15, "Med_ms": 24.94,
            "Max_ms": 2040.00, "p90_ms": 33.21, "p95_ms": 37.73, "p95_Valid_ms": 37.73,
            "Http_Req_Failed_Pct": 0.00, "Http_Req_Failed_Count": 0,
            "Checks_Total": 7012, "Checks_Succeeded_Pct": 99.98, "SLA_2s_Violations": 1
        },
        {
            "Etapa": 5, "VUs": 50, "Duracao": "2m", "Iteracoes": 5824, "Http_Reqs": 5824,
            "Reqs_Status_200": 5824, "RPS": 48.163655, "RPS_Efetivo_200": 48.163655,
            "Avg_ms": 31.22, "Avg_Valid_ms": 31.22, "Min_ms": 3.76, "Med_ms": 25.59,
            "Max_ms": 5350.00, "p90_ms": 36.65, "p95_ms": 46.18, "p95_Valid_ms": 46.18,
            "Http_Req_Failed_Pct": 0.00, "Http_Req_Failed_Count": 0,
            "Checks_Total": 11648, "Checks_Succeeded_Pct": 99.94, "SLA_2s_Violations": 6
        },
        {
            "Etapa": 6, "VUs": 75, "Duracao": "2m", "Iteracoes": 8657, "Http_Reqs": 8657,
            "Reqs_Status_200": 8657, "RPS": 70.087865, "RPS_Efetivo_200": 70.087865,
            "Avg_ms": 40.91, "Avg_Valid_ms": 40.91, "Min_ms": 4.25, "Med_ms": 27.00,
            "Max_ms": 10060.00, "p90_ms": 42.22, "p95_ms": 72.20, "p95_Valid_ms": 72.20,
            "Http_Req_Failed_Pct": 0.00, "Http_Req_Failed_Count": 0,
            "Checks_Total": 17314, "Checks_Succeeded_Pct": 99.95, "SLA_2s_Violations": 7
        },
        {
            "Etapa": 7, "VUs": 100, "Duracao": "2m", "Iteracoes": 11548, "Http_Reqs": 11548,
            "Reqs_Status_200": 11548, "RPS": 81.982386, "RPS_Efetivo_200": 81.982386,
            "Avg_ms": 44.77, "Avg_Valid_ms": 44.77, "Min_ms": 4.02, "Med_ms": 22.08,
            "Max_ms": 22220.00, "p90_ms": 34.67, "p95_ms": 41.40, "p95_Valid_ms": 41.40,
            "Http_Req_Failed_Pct": 0.00, "Http_Req_Failed_Count": 0,
            "Checks_Total": 23096, "Checks_Succeeded_Pct": 99.94, "SLA_2s_Violations": 12
        },
        {
            "Etapa": 8, "VUs": 150, "Duracao": "2m", "Iteracoes": 17246, "Http_Reqs": 17246,
            "Reqs_Status_200": 17246, "RPS": 131.858309, "RPS_Efetivo_200": 131.858309,
            "Avg_ms": 48.80, "Avg_Valid_ms": 48.80, "Min_ms": 3.19, "Med_ms": 19.06,
            "Max_ms": 23530.00, "p90_ms": 32.86, "p95_ms": 56.27, "p95_Valid_ms": 56.27,
            "Http_Req_Failed_Pct": 0.00, "Http_Req_Failed_Count": 0,
            "Checks_Total": 34492, "Checks_Succeeded_Pct": 99.95, "SLA_2s_Violations": 15
        },
        {
            "Etapa": 9, "VUs": 175, "Duracao": "2m", "Iteracoes": 17252, "Http_Reqs": 17252,
            "Reqs_Status_200": 17227, "RPS": 122.688855, "RPS_Efetivo_200": 122.511067,
            "Avg_ms": 229.05, "Avg_Valid_ms": 142.34, "Min_ms": 3.19, "Med_ms": 18.73,
            "Max_ms": 59970.00, "p90_ms": 33.04, "p95_ms": 44.80, "p95_Valid_ms": 44.40,
            "Http_Req_Failed_Pct": 0.14, "Http_Req_Failed_Count": 25,
            "Checks_Total": 34504, "Checks_Succeeded_Pct": 99.65, "SLA_2s_Violations": 95
        },
        {
            "Etapa": 10, "VUs": 200, "Duracao": "2m", "Iteracoes": 17124, "Http_Reqs": 17124,
            "Reqs_Status_200": 17074, "RPS": 123.924892, "RPS_Efetivo_200": 123.563049,
            "Avg_ms": 421.33, "Avg_Valid_ms": 246.91, "Min_ms": 3.58, "Med_ms": 19.99,
            "Max_ms": 59990.00, "p90_ms": 38.20, "p95_ms": 67.61, "p95_Valid_ms": 63.28,
            "Http_Req_Failed_Pct": 0.29, "Http_Req_Failed_Count": 50,
            "Checks_Total": 34248, "Checks_Succeeded_Pct": 99.36, "SLA_2s_Violations": 168
        },
        {
            "Etapa": 11, "VUs": 300, "Duracao": "2m", "Iteracoes": 17495, "Http_Reqs": 17495,
            "Reqs_Status_200": 17334, "RPS": 116.632822, "RPS_Efetivo_200": 115.559493,
            "Avg_ms": 1100.00, "Avg_Valid_ms": 562.43, "Min_ms": 3.19, "Med_ms": 20.55,
            "Max_ms": 59990.00, "p90_ms": 34.68, "p95_ms": 49.13, "p95_Valid_ms": 43.60,
            "Http_Req_Failed_Pct": 0.92, "Http_Req_Failed_Count": 161,
            "Checks_Total": 34990, "Checks_Succeeded_Pct": 98.24, "SLA_2s_Violations": 453
        },
        {
            "Etapa": 12, "VUs": 400, "Duracao": "2m", "Iteracoes": 17607, "Http_Reqs": 17607,
            "Reqs_Status_200": 17243, "RPS": 117.379532, "RPS_Efetivo_200": 114.952876,
            "Avg_ms": 1780.00, "Avg_Valid_ms": 559.23, "Min_ms": 3.63, "Med_ms": 20.35,
            "Max_ms": 59990.00, "p90_ms": 53.63, "p95_ms": 247.93, "p95_Valid_ms": 135.53,
            "Http_Req_Failed_Pct": 2.06, "Http_Req_Failed_Count": 364,
            "Checks_Total": 35214, "Checks_Succeeded_Pct": 97.12, "SLA_2s_Violations": 648
        },
        {
            "Etapa": 13, "VUs": 500, "Duracao": "2m", "Iteracoes": 20863, "Http_Reqs": 20863,
            "Reqs_Status_200": 16671, "RPS": 139.086426, "RPS_Efetivo_200": 111.139815,
            "Avg_ms": 2020.00, "Avg_Valid_ms": 1120.00, "Min_ms": 0.00, "Med_ms": 21.12,
            "Max_ms": 60000.00, "p90_ms": 48.48, "p95_ms": 445.53, "p95_Valid_ms": 265.87,
            "Http_Req_Failed_Pct": 20.09, "Http_Req_Failed_Count": 4192,
            "Checks_Total": 41726, "Checks_Succeeded_Pct": 87.89, "SLA_2s_Violations": 860
        },
        {
            "Etapa": 14, "VUs": 750, "Duracao": "2m", "Iteracoes": 49835, "Http_Reqs": 49835,
            "Reqs_Status_200": 16264, "RPS": 332.232575, "RPS_Efetivo_200": 108.426417,
            "Avg_ms": 934.72, "Avg_Valid_ms": 902.29, "Min_ms": 0.00, "Med_ms": 0.00,
            "Max_ms": 60000.00, "p90_ms": 35.10, "p95_ms": 58.48, "p95_Valid_ms": 308.73,
            "Http_Req_Failed_Pct": 67.36, "Http_Req_Failed_Count": 33571,
            "Checks_Total": 99670, "Checks_Succeeded_Pct": 65.27, "SLA_2s_Violations": 1043
        },
        {
            "Etapa": 15, "VUs": 1000, "Duracao": "2m", "Iteracoes": 74564, "Http_Reqs": 74565,
            "Reqs_Status_200": 16645, "RPS": 497.098871, "RPS_Efetivo_200": 110.966418,
            "Avg_ms": 669.31, "Avg_Valid_ms": 1100.00, "Min_ms": 0.00, "Med_ms": 0.00,
            "Max_ms": 60000.00, "p90_ms": 29.30, "p95_ms": 48.26, "p95_Valid_ms": 241.46,
            "Http_Req_Failed_Pct": 77.67, "Http_Req_Failed_Count": 57920,
            "Checks_Total": 149130, "Checks_Succeeded_Pct": 60.42, "SLA_2s_Violations": 1101
        }
    ]

    df = pd.DataFrame(dados)

    # 2. Exportar para CSV
    csv_filename = "benchmark_cashfy_resultados.csv"
    df.to_csv(csv_filename, index=False, encoding="utf-8-sig")

    # 3. Exibir Tabela Comparativa Completa no Terminal
    pd.set_option("display.max_columns", None)
    pd.set_option("display.width", 1300)

    print("=" * 130)
    print("RELATÓRIO FINAL DE BENCHMARK DE DESEMPENHO - CASHFY (Grafana k6 v2.4.0 | Windows 11 + XAMPP | 5 a 1.000 VUs)")
    print("=" * 130)

    colunas_exibicao = [
        "Etapa", "VUs", "Duracao", "Http_Reqs", "Reqs_Status_200", "RPS", "RPS_Efetivo_200",
        "Avg_ms", "Med_ms", "p90_ms", "p95_ms", "Max_ms", "Http_Req_Failed_Pct", "Checks_Succeeded_Pct"
    ]
    print(df[colunas_exibicao].to_string(index=False))
    print("=" * 130)
    print(f"[OK] Arquivo CSV gerado com sucesso: {csv_filename}")
    print("\nDIAGNÓSTICO EXECUTIVO DAS 15 ETAPAS:")
    print(" 1. Capacidade Máxima Saudável (0% Falhas): 150 VUs (Etapa 8 -> 131.86 RPS, Avg = 48.80 ms).")
    print(" 2. Início da Saturação de Threads (Apache ThreadsPerChild=150): 175 VUs (Etapa 9 -> Avg = 229.05 ms, 25 timeouts de 60s).")
    print(" 3. Pico de Enfileiramento antes de Rejeição TCP: 400 VUs (Etapa 12 -> Avg = 1780 ms, p95 = 247.93 ms, 2.06% falhas).")
    print(" 4. Colapso de Backlog TCP (500 a 1.000 VUs): Falhas saltam de 20.09% (500 VUs) para 67.36% (750 VUs) e 77.67% (1.000 VUs).")
    print("    Nota: Acima de 500 VUs, as rejeições TCP em 0ms reduzem artificialmente o Avg/p95 bruto e inflam o RPS bruto,")
    print("    mas o Throughput Real (Status 200) permanece estagnado em ~110 req/s (~16.600 páginas a cada 2 minutos).")
    print("=" * 130)

    # 4. Criar Painel Visual com os 4 Gráficos Comparativos
    plt.style.use("seaborn-v0_8-whitegrid" if "seaborn-v0_8-whitegrid" in plt.style.available else "default")
    fig, axes = plt.subplots(2, 2, figsize=(18, 11))
    fig.suptitle(
        "Benchmark Progressivo de Desempenho - CashFY (5 a 1.000 VUs | Grafana k6)",
        fontsize=16, fontweight="bold", y=0.98
    )

    vus = df["VUs"]

    # --- Gráfico 1: VUs vs Tempo Médio de Resposta (Avg Geral vs Avg Respostas Válidas) ---
    ax1 = axes[0, 0]
    ax1.plot(vus, df["Avg_ms"], marker="o", linewidth=2.5, color="#1f77b4", label="Avg Geral k6 (Inclui erros 0ms)")
    ax1.plot(vus, df["Avg_Valid_ms"], marker="s", linewidth=2, linestyle="--", color="#ff7f0e", label="Avg Respostas Válidas (Status 200)")
    ax1.axvline(x=150, color="#2ca02c", linestyle="--", alpha=0.8, label="Limite Saudável (150 VUs)")
    ax1.axvline(x=500, color="#d62728", linestyle=":", alpha=0.9, label="Colapso Backlog TCP (500 VUs)")
    for x, y in zip(vus, df["Avg_ms"]):
        if x in [5, 100, 150, 200, 300, 400, 500, 750, 1000]:
            ax1.annotate(f"{y:.0f}ms", (x, y), textcoords="offset points", xytext=(0, 7), ha="center", fontsize=8)
    ax1.set_title("Usuários Virtuais (VUs) vs Tempo Médio de Resposta (ms)", fontweight="bold")
    ax1.set_xlabel("Usuários Virtuais (VUs)")
    ax1.set_ylabel("Tempo Médio (ms)")
    ax1.grid(True, linestyle="--", alpha=0.5)
    ax1.legend(loc="upper left", fontsize=8.5)

    # --- Gráfico 2: VUs vs Percentil 95 (p95 Geral vs p95 Respostas Válidas) ---
    ax2 = axes[0, 1]
    ax2.plot(vus, df["p95_ms"], marker="s", linewidth=2.5, color="#9467bd", label="p95 Geral k6 (ms)")
    ax2.plot(vus, df["p95_Valid_ms"], marker="^", linewidth=2, linestyle="--", color="#d62728", label="p95 Respostas Válidas (Status 200)")
    ax2.plot(vus, df["Med_ms"], marker="d", linewidth=1.5, linestyle=":", color="#7f7f7f", label="Mediana Geral (ms)")
    ax2.axvline(x=400, color="#ff7f0e", linestyle="--", alpha=0.8, label="Disparo p95 (400-500 VUs)")
    for x, y in zip(vus, df["p95_ms"]):
        if x in [5, 75, 150, 300, 400, 500, 750, 1000]:
            ax2.annotate(f"{y:.1f}", (x, y), textcoords="offset points", xytext=(0, 7), ha="center", fontsize=8)
    ax2.set_title("Usuários Virtuais (VUs) vs Latência p95 e Mediana (ms)", fontweight="bold")
    ax2.set_xlabel("Usuários Virtuais (VUs)")
    ax2.set_ylabel("Latência (ms)")
    ax2.grid(True, linestyle="--", alpha=0.5)
    ax2.legend(loc="upper left", fontsize=8.5)

    # --- Gráfico 3: VUs vs RPS (Throughput Bruto vs Throughput Real Status 200) ---
    ax3 = axes[1, 0]
    ax3.plot(vus, df["RPS"], marker="o", linewidth=2.2, linestyle="--", color="#7f7f7f", label="RPS Bruto (Inflado por falhas em 0ms)")
    ax3.plot(vus, df["RPS_Efetivo_200"], marker="o", linewidth=2.8, color="#2ca02c", label="RPS Real Efetivo (Apenas Status 200)")
    ax3.axvline(x=150, color="#d62728", linestyle="--", alpha=0.8, label="Pico Real Apache (131.86 RPS @ 150 VUs)")
    ax3.fill_between(vus, df["RPS_Efetivo_200"], color="#2ca02c", alpha=0.15)
    for x, y in zip(vus, df["RPS_Efetivo_200"]):
        if x in [5, 50, 100, 150, 300, 500, 750, 1000]:
            ax3.annotate(f"{y:.1f}", (x, y), textcoords="offset points", xytext=(0, -14), ha="center", fontsize=8, fontweight="bold", color="#1b611b")
    ax3.set_title("Usuários Virtuais (VUs) vs Throughput (RPS Bruto vs RPS Real Status 200)", fontweight="bold")
    ax3.set_xlabel("Usuários Virtuais (VUs)")
    ax3.set_ylabel("Requisições por Segundo (req/s)")
    ax3.grid(True, linestyle="--", alpha=0.5)
    ax3.legend(loc="upper left", fontsize=8.5)

    # --- Gráfico 4: Taxa de Falhas HTTP (%) e Violações de SLA (> 2s) por Etapa ---
    ax4 = axes[1, 1]
    etapas_labels = [f"E{e}\n({v})" for e, v in zip(df["Etapa"], df["VUs"])]
    x_pos = range(len(df))

    bars = ax4.bar(x_pos, df["Http_Req_Failed_Pct"], width=0.55, color="#d62728", alpha=0.85, label="Taxa de Falhas HTTP (%)")
    ax4.set_title("Taxa de Falhas HTTP (%) e Violações de SLA (> 2s) por Etapa", fontweight="bold")
    ax4.set_xlabel("Etapa (VUs)")
    ax4.set_ylabel("Falhas HTTP (%)", color="#d62728")
    ax4.set_xticks(list(x_pos))
    ax4.set_xticklabels(etapas_labels, fontsize=7.5)
    ax4.set_ylim(0, 95)
    ax4.grid(True, linestyle="--", alpha=0.5)

    for bar, count in zip(bars, df["Http_Req_Failed_Count"]):
        height = bar.get_height()
        if height > 0:
            ax4.annotate(
                f"{height:.1f}%\n({count})",
                xy=(bar.get_x() + bar.get_width() / 2, height),
                xytext=(0, 3), textcoords="offset points",
                ha="center", va="bottom", fontsize=7, fontweight="bold", color="#d62728"
            )

    ax4_twin = ax4.twinx()
    ax4_twin.plot(
        list(x_pos), df["SLA_2s_Violations"], color="#ff7f0e", marker="D",
        linewidth=2, linestyle="-.", label="Reqs > 2s (Violação SLA)"
    )
    ax4_twin.set_ylabel("Qtd. Requisições > 2 segundos", color="#ff7f0e")
    ax4_twin.set_ylim(0, max(df["SLA_2s_Violations"]) * 1.2)
    ax4_twin.grid(False)

    lines_1, labels_1 = ax4.get_legend_handles_labels()
    lines_2, labels_2 = ax4_twin.get_legend_handles_labels()
    ax4.legend(lines_1 + lines_2, labels_1 + labels_2, loc="upper left", fontsize=8.5)

    plt.tight_layout(rect=[0, 0.02, 1, 0.95])
    img_filename = "benchmark_cashfy_graficos.png"
    plt.savefig(img_filename, dpi=300)
    print(f"[OK] Painel de gráficos salvo em alta resolução: {img_filename}")
    plt.show()

if __name__ == "__main__":
    main()
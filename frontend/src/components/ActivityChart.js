import React from 'react';
import {
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    BarChart,
    Bar
} from 'recharts';

const ActivityChart = ({ data, period }) => {
    const [metric, setMetric] = React.useState('distance');
    const [chartType, setChartType] = React.useState('line');

    const formatTooltip = (value, name) => {
        switch (name) {
            case 'distance':
                return [`${value} km`, 'Distance'];
            case 'time':
                return [`${value} h`, 'Temps'];
            case 'count':
                return [`${value}`, 'Activités'];
            case 'elevation':
                return [`${value} m`, 'Dénivelé'];
            default:
                return [value, name];
        }
    };

    const formatXAxisLabel = (tickItem) => {
        if (period === 'week' || period === 'month') {
            // Format: 2024-01-15 -> 15/01
            const date = new Date(tickItem);
            return `${date.getDate()}/${date.getMonth() + 1}`;
        } else {
            // Format: 2024-01 -> Jan 24
            const [year, month] = tickItem.split('-');
            const monthNames = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun',
                'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
            return `${monthNames[parseInt(month) - 1]} ${year.slice(-2)}`;
        }
    };

    const getMetricColor = (metricName) => {
        const colors = {
            distance: '#8884d8',
            time: '#82ca9d',
            count: '#ffc658',
            elevation: '#ff7300'
        };
        return colors[metricName] || '#8884d8';
    };

    if (!data || data.length === 0) {
        return (
            <div className="chart-container">
                <div className="chart-empty">
                    <p>📊 Aucune donnée disponible pour cette période</p>
                </div>
            </div>
        );
    }

    const ChartComponent = chartType === 'line' ? LineChart : BarChart;
    const DataComponent = chartType === 'line'
        ? <Line
            type="monotone"
            dataKey={metric}
            stroke={getMetricColor(metric)}
            strokeWidth={2}
            dot={{ r: 4, fill: getMetricColor(metric) }}
        />
        : <Bar dataKey={metric} fill={getMetricColor(metric)} />;

    return (
        <div className="chart-container">
            <div className="chart-controls">
                <div className="metric-selector">
                    <label>Métrique :</label>
                    <select value={metric} onChange={(e) => setMetric(e.target.value)}>
                        <option value="distance">Distance (km)</option>
                        <option value="time">Temps (h)</option>
                        <option value="count">Nombre d'activités</option>
                        <option value="elevation">Dénivelé (m)</option>
                    </select>
                </div>

                <div className="chart-type-selector">
                    <label>Type :</label>
                    <select value={chartType} onChange={(e) => setChartType(e.target.value)}>
                        <option value="line">Ligne</option>
                        <option value="bar">Barres</option>
                    </select>
                </div>
            </div>

            <ResponsiveContainer width="100%" height={300}>
                <ChartComponent data={data}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis
                        dataKey="date"
                        tickFormatter={formatXAxisLabel}
                        angle={-45}
                        textAnchor="end"
                        height={60}
                    />
                    <YAxis />
                    <Tooltip
                        formatter={formatTooltip}
                        labelFormatter={(label) => `Date: ${formatXAxisLabel(label)}`}
                    />
                    {DataComponent}
                </ChartComponent>
            </ResponsiveContainer>

            <div className="chart-summary">
                <div className="summary-item">
                    <span className="summary-label">Total points:</span>
                    <span className="summary-value">{data.length}</span>
                </div>
                <div className="summary-item">
                    <span className="summary-label">Période:</span>
                    <span className="summary-value">{period}</span>
                </div>
            </div>
        </div>
    );
};

export default ActivityChart;
